<?php

namespace App\Jobs;

use App\Models\Artist;
use App\Models\CardType;
use App\Models\Rarity;
use App\Services\TcgdexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sincronizza le entità di catalogo da TCGdex:
 *  - Rarità       → tabella rarities
 *  - Tipi carta   → tabella card_types (categorie: Pokémon, Trainer, Energy)
 *  - Artisti      → tabella artists
 *
 * Va eseguito PRIMA di SyncCardsJob in modo che le FK siano già risolte.
 * Idempotente: usa updateOrCreate/firstOrCreate su ogni record.
 */
class SyncCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 3;

    public function handle(TcgdexService $tcgdex): void
    {
        Log::info('SyncCatalogJob: avvio sincronizzazione catalogo');

        $this->syncRarities($tcgdex);
        $this->syncCardTypes($tcgdex);
        $this->syncArtists($tcgdex);

        Log::info('SyncCatalogJob: completato');
    }

    /**
     * Sincronizza le rarità da TCGdex (en).
     * TCGdex restituisce un array di stringhe es. ["Common", "Uncommon", "Rare", ...]
     */
    private function syncRarities(TcgdexService $tcgdex): void
    {
        $rarities = $tcgdex->withLang('en')->fetchRarities();

        if (empty($rarities)) {
            Log::warning('SyncCatalogJob: nessuna rarità ricevuta da TCGdex');
            return;
        }

        $synced = 0;

        foreach ($rarities as $name) {
            if (! is_string($name) || trim($name) === '') {
                continue;
            }

            // firstOrCreate per non sovrascrivere eventuali rarità inserite manualmente
            Rarity::firstOrCreate(['name' => trim($name)]);
            $synced++;
        }

        Log::info("SyncCatalogJob: sincronizzate {$synced} rarità");
    }

    /**
     * Sincronizza le categorie di carte da TCGdex (en).
     * TCGdex restituisce: ["Pokémon", "Trainer", "Energy"]
     */
    private function syncCardTypes(TcgdexService $tcgdex): void
    {
        $categories = $tcgdex->withLang('en')->fetchCategories();

        if (empty($categories)) {
            Log::warning('SyncCatalogJob: nessuna categoria ricevuta da TCGdex');
            return;
        }

        $synced = 0;

        foreach ($categories as $name) {
            if (! is_string($name) || trim($name) === '') {
                continue;
            }

            CardType::firstOrCreate(['name' => trim($name)]);
            $synced++;
        }

        Log::info("SyncCatalogJob: sincronizzati {$synced} tipi carta");
    }

    /**
     * Sincronizza gli illustratori da TCGdex (en).
     * TCGdex restituisce un array di stringhe con i nomi degli artisti.
     */
    private function syncArtists(TcgdexService $tcgdex): void
    {
        $illustrators = $tcgdex->withLang('en')->fetchIllustrators();

        if (empty($illustrators)) {
            Log::warning('SyncCatalogJob: nessun illustratore ricevuto da TCGdex');
            return;
        }

        $synced = 0;

        foreach ($illustrators as $name) {
            if (! is_string($name) || trim($name) === '') {
                continue;
            }

            Artist::firstOrCreate(['name' => trim($name)]);
            $synced++;
        }

        Log::info("SyncCatalogJob: sincronizzati {$synced} artisti");
    }
}
