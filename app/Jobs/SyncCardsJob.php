<?php

namespace App\Jobs;

use App\Exceptions\TcgdexException;
use App\Models\Artist;
use App\Models\Card;
use App\Models\CardType;
use App\Models\Language;
use App\Models\Rarity;
use App\Models\Set;
use App\Services\TcgdexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sincronizza tutte le carte di un set da TCGdex.
 *
 * Dispatchato dal comando sync:cards (anche dalla schedulazione settimanale).
 *  - Dettaglio di ogni carta (en): upsert in cards e snapshot giornaliero dei prezzi Cardmarket
 *  - Nomi in tutte le lingue: una chiamata per lingua sull'intero set (inglese compreso),
 *    perché /sets/{id} restituisce già il nome di ogni carta nella lingua richiesta
 *
 * Se TCGdex è irraggiungibile il job fallisce e la coda lo ritenta; un errore su una singola
 * carta viene registrato e la sincronizzazione prosegue con le altre.
 */
class SyncCardsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 3;

    /** Attesa tra i tentativi: TCGdex in difficoltà ha bisogno di tempo. */
    public array $backoff = [60, 300];

    /** Lo stesso set non viene messo in coda due volte finché il job non è finito. */
    public int $uniqueFor = 3600;

    /** Campi di pricing.cardmarket che non sono prezzi. */
    private const PRICE_META_FIELDS = ['updated', 'unit', 'idProduct'];

    public function __construct(private string $setTcgdexId)
    {
    }

    public function uniqueId(): string
    {
        return $this->setTcgdexId;
    }

    public function handle(TcgdexService $tcgdex): void
    {
        // withTrashed: un set nascosto dall'admin resta nascosto ma le sue carte si aggiornano
        $set = Set::withTrashed()->where('tcgdex_id', $this->setTcgdexId)->first();

        if (! $set) {
            Log::warning("SyncCardsJob: set {$this->setTcgdexId} non trovato nel DB");
            return;
        }

        $enSet = $tcgdex->withLang('en')->fetchSet($this->setTcgdexId);
        $summaries = $enSet['cards'] ?? [];

        if (empty($summaries)) {
            Log::warning("SyncCardsJob: nessuna carta ricevuta per {$this->setTcgdexId}");
            return;
        }

        $cardIds = [];   // tcgdex_id → id nel DB
        $failed  = 0;

        foreach ($summaries as $summary) {
            $tcgdexId = $summary['id'] ?? null;

            if (! $tcgdexId || empty($summary['localId'])) {
                continue;
            }

            try {
                $detail = $tcgdex->withLang('en')->fetchCard($tcgdexId);

                if (! $detail) {
                    continue;
                }

                $card = $this->upsertCard($set, $tcgdexId, (string) $summary['localId'], $detail);
                $this->syncCardmarketPrices($card, $detail);
                $cardIds[$tcgdexId] = $card->id;
            } catch (TcgdexException $e) {
                // L'API non risponde: inutile continuare carta per carta, si ritenta il job
                throw $e;
            } catch (\Throwable $e) {
                $failed++;
                Log::error("SyncCardsJob: errore carta {$tcgdexId} → {$e->getMessage()}");
            }
        }

        if ($cardIds === [] && $failed > 0) {
            throw new \RuntimeException("SyncCardsJob [{$this->setTcgdexId}]: nessuna carta salvata, {$failed} errori");
        }

        $this->syncTranslations($tcgdex, $enSet, $cardIds);

        Log::info("SyncCardsJob [{$this->setTcgdexId}]: sincronizzate " . count($cardIds) . " carte, {$failed} errori");
    }

    private function upsertCard(Set $set, string $tcgdexId, string $localId, array $detail): Card
    {
        // Risolvi le FK opzionali cercando o creando le entità correlate
        $artistId   = $this->resolveArtist($detail['illustrator'] ?? null);
        $rarityId   = $this->resolveRarity($detail['rarity'] ?? null);
        $cardTypeId = $this->resolveCardType($detail['category'] ?? null);

        // withTrashed: aggiorna anche una carta nascosta invece di violare l'unique su tcgdex_id
        return Card::withTrashed()->updateOrCreate(
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
     * Snapshot giornaliero dei prezzi Cardmarket: un valore per tipo (trend, avg, low, avg7,
     * trend-holo...) e per giorno di aggiornamento su Cardmarket. Rilanciare la sync lo
     * aggiorna invece di duplicarlo. Path TCGdex: card.pricing.cardmarket.
     */
    private function syncCardmarketPrices(Card $card, array $detail): void
    {
        $pricing = $detail['pricing']['cardmarket'] ?? null;

        if (! is_array($pricing)) {
            return;
        }

        $updatedAt = isset($pricing['updated']) ? Carbon::parse($pricing['updated']) : now();
        $currency  = strtoupper((string) ($pricing['unit'] ?? 'EUR'));

        $rows = [];
        foreach ($pricing as $type => $value) {
            if (in_array($type, self::PRICE_META_FIELDS, true) || ! is_numeric($value)) {
                continue;
            }

            $rows[] = [
                'card_id'     => $card->id,
                'source'      => 'cardmarket',
                'price_type'  => $type,
                'price'       => $value,
                'currency'    => $currency,
                'recorded_at' => $updatedAt,
                'recorded_on' => $updatedAt->toDateString(),
            ];
        }

        if ($rows) {
            DB::table('card_prices')->upsert(
                $rows,
                ['card_id', 'source', 'price_type', 'recorded_on'],
                ['price', 'currency', 'recorded_at'],
            );
        }
    }

    /**
     * Nomi delle carte in ogni lingua del DB supportata da TCGdex, inglese compreso.
     *
     * @param  array<string, int>  $cardIds  tcgdex_id → id nel DB
     */
    private function syncTranslations(TcgdexService $tcgdex, array $enSet, array $cardIds): void
    {
        if ($cardIds === []) {
            return;
        }

        foreach (Language::whereNotNull('code')->get() as $language) {
            $lang = TcgdexService::apiLang($language->code);

            if (! $lang) {
                continue;
            }

            // L'inglese è già stato scaricato; le altre lingue con una chiamata sul set intero.
            // 404: il set non esiste in quella lingua (es. set solo giapponesi o solo occidentali).
            $localized = $lang === 'en' ? $enSet : $tcgdex->withLang($lang)->fetchSet($this->setTcgdexId);

            $rows = [];
            foreach ($localized['cards'] ?? [] as $summary) {
                $cardId = $cardIds[$summary['id'] ?? ''] ?? null;

                if ($cardId && ! empty($summary['name'])) {
                    $rows[] = [
                        'card_id'     => $cardId,
                        'language_id' => $language->id,
                        'name'        => $summary['name'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
            }

            if ($rows) {
                DB::table('card_translations')->upsert($rows, ['card_id', 'language_id'], ['name', 'updated_at']);
            }
        }
    }

    /**
     * Trova o crea un artista per nome. Ritorna null se il nome non è valorizzato.
     * Un artista nascosto dall'admin viene riusato (senza ripristinarlo) invece di
     * provare a ricrearlo, cosa che violerebbe l'unique sul nome.
     */
    private function resolveArtist(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return Artist::withTrashed()->firstOrCreate(['name' => $name])->id;
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

        $id = Rarity::withTrashed()->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id');

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
        $id = CardType::withTrashed()->whereRaw('LOWER(name) = ?', [strtolower($category)])->value('id');

        // Fallback: rimuovi caratteri non ASCII (es. "Pokémon" → "Pokemon")
        if (! $id) {
            $normalized = preg_replace('/[^a-zA-Z0-9 ]/', '', $category);
            $id = CardType::withTrashed()
                ->whereRaw("LOWER(REGEXP_REPLACE(name, '[^a-zA-Z0-9 ]', '')) = ?", [strtolower($normalized)])
                ->value('id');
        }

        if (! $id) {
            Log::debug("SyncCardsJob: tipo carta non trovato → \"{$category}\"");
        }

        return $id;
    }
}
