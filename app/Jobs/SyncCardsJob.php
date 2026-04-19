<?php

namespace App\Jobs;

use App\Models\Artist;
use App\Models\Card;
use App\Models\CardPrice;
use App\Models\CardTranslation;
use App\Models\CardType;
use App\Models\Language;
use App\Models\Rarity;
use App\Models\Set;
use App\Services\TcgdexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sincronizza tutte le carte di un set da TCGdex.
 *
 * Dispatchato da SyncSetsJob o dal comando sync:cards.
 * Per ogni carta:
 *  - Upsert del record canonico (en) in cards
 *  - Upsert delle traduzioni in card_translations
 *  - Salvataggio dei prezzi Cardmarket in card_prices (storico)
 */
class SyncCardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 3;

    public function __construct(private string $setTcgdexId)
    {
    }

    public function handle(TcgdexService $tcgdex): void
    {
        $set = Set::where('tcgdex_id', $this->setTcgdexId)->first();

        if (! $set) {
            Log::warning("SyncCardsJob: set {$this->setTcgdexId} non trovato nel DB");
            return;
        }

        Log::info("SyncCardsJob: sincronizzazione carte per set {$this->setTcgdexId}");

        // Fetch lista carte in inglese (canonico)
        $cards = $tcgdex->withLang('en')->fetchCardsForSet($this->setTcgdexId);

        if (empty($cards)) {
            Log::warning("SyncCardsJob: nessuna carta ricevuta per {$this->setTcgdexId}");
            return;
        }

        $synced  = 0;
        $firstCard = true;

        foreach ($cards as $cardSummary) {
            $localId  = $cardSummary['localId'] ?? null;
            $tcgdexId = $cardSummary['id'] ?? ($this->setTcgdexId . '-' . $localId);

            if (! $localId) {
                continue;
            }

            try {
                // Dettaglio completo della carta
                $detail = $tcgdex->withLang('en')->fetchCard($tcgdexId);

                if (! $detail) {
                    continue;
                }

                // Log della struttura della prima carta per verifica field names
                if ($firstCard) {
                    Log::debug('SyncCardsJob sample card keys: ' . implode(', ', array_keys($detail)));
                    Log::debug('SyncCardsJob sample pricing: ' . json_encode($detail['pricing'] ?? 'missing'));
                    $firstCard = false;
                }

                $card = $this->upsertCard($set, $tcgdexId, $localId, $detail);

                // Prezzi Cardmarket (se presenti)
                $this->syncCardmarketPrice($card, $detail);

                // Traduzioni
                $this->syncTranslations($tcgdex, $card, $tcgdexId);

                $synced++;
            } catch (\Throwable $e) {
                Log::error("SyncCardsJob: errore carta {$tcgdexId} → {$e->getMessage()}");
            }
        }

        Log::info("SyncCardsJob [{$this->setTcgdexId}]: sincronizzate {$synced} carte");
    }

    private function upsertCard(Set $set, string $tcgdexId, string $localId, array $detail): Card
    {
        // Risolvi le FK opzionali cercando o creando le entità correlate
        $artistId   = $this->resolveArtist($detail['illustrator'] ?? null);
        $rarityId   = $this->resolveRarity($detail['rarity'] ?? null);
        $cardTypeId = $this->resolveCardType($detail['category'] ?? null);

        return Card::updateOrCreate(
            ['tcgdex_id' => $tcgdexId],
            [
                'set_id'       => $set->id,
                'artist_id'    => $artistId,
                'rarity_id'    => $rarityId,
                'card_type_id' => $cardTypeId,
                'local_id'     => $localId,
                'image_url'    => $detail['image'] ?? null,
                'stage'        => $detail['stage'] ?? null,
                'energy_types' => $detail['types'] ?? null,
            ]
        );
    }

    /**
     * Salva un record di prezzo Cardmarket (storico — non sovrascrive i precedenti).
     * Path TCGdex: card.pricing.cardmarket.{trend|averageSellPrice|lowPrice}
     */
    private function syncCardmarketPrice(Card $card, array $detail): void
    {
        $pricing = $detail['pricing']['cardmarket'] ?? null;

        if (! $pricing) {
            return;
        }

        // Mappa campo API → price_type nel DB
        $priceFields = [
            'trend'            => 'trend',
            'averageSellPrice' => 'avg',
            'lowPrice'         => 'low',
        ];

        foreach ($priceFields as $apiField => $priceType) {
            $value = $pricing[$apiField] ?? null;

            if ($value === null) {
                continue;
            }

            CardPrice::create([
                'card_id'     => $card->id,
                'source'      => 'cardmarket',
                'price_type'  => $priceType,
                'price'       => $value,
                'recorded_at' => now(),
            ]);
        }
    }

    private function syncTranslations(TcgdexService $tcgdex, Card $card, string $tcgdexId): void
    {
        $languages = Language::whereNotNull('code')->get();

        foreach ($languages as $language) {
            $lang = strtolower($language->code);

            if ($lang === 'en') {
                continue;
            }

            if (! in_array($lang, TcgdexService::SUPPORTED_LANGS)) {
                continue;
            }

            try {
                $detail = $tcgdex->withLang($lang)->fetchCard($tcgdexId);
                $name   = $detail['name'] ?? null;

                if (! $name) {
                    continue;
                }

                DB::table('card_translations')->updateOrInsert(
                    ['card_id' => $card->id, 'language_id' => $language->id],
                    ['name' => $name, 'updated_at' => now(), 'created_at' => now()]
                );
            } catch (\Throwable $e) {
                Log::warning("SyncCardsJob [{$lang}]: errore traduzione {$tcgdexId} → {$e->getMessage()}");
            }
        }
    }

    /**
     * Trova o crea un artista per nome. Ritorna null se il nome non è valorizzato.
     */
    private function resolveArtist(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return Artist::firstOrCreate(['name' => $name])->id;
    }

    /**
     * Trova una rarità esistente per nome (case-insensitive).
     * Non crea nuove rarità — devono essere presenti dopo sync:catalog.
     */
    private function resolveRarity(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        $id = Rarity::whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id');

        if (! $id) {
            Log::debug("SyncCardsJob: rarità non trovata → \"{$name}\"");
        }

        return $id;
    }

    /**
     * Trova un tipo carta esistente per nome (Pokémon, Trainer, Energy).
     * Il match rimuove gli accenti per gestire differenze di encoding.
     */
    private function resolveCardType(?string $category): ?int
    {
        if (! $category) {
            return null;
        }

        // Match diretto
        $id = CardType::whereRaw('LOWER(name) = ?', [strtolower($category)])->value('id');

        // Fallback: rimuovi caratteri non ASCII (es. "Pokémon" → "Pokemon")
        if (! $id) {
            $normalized = preg_replace('/[^a-zA-Z0-9 ]/', '', $category);
            $id = CardType::whereRaw('LOWER(REGEXP_REPLACE(name, "[^a-zA-Z0-9 ]", "")) = ?', [strtolower($normalized)])->value('id');
        }

        if (! $id) {
            Log::debug("SyncCardsJob: tipo carta non trovato → \"{$category}\"");
        }

        return $id;
    }
}
