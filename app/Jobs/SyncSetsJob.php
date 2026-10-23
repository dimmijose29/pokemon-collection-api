<?php

namespace App\Jobs;

use App\Exceptions\TcgdexException;
use App\Models\Language;
use App\Models\Series;
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
 * Sincronizza tutti i set da TCGdex per tutte le lingue presenti nel DB.
 *
 * Flusso:
 *  1. Recupera i set in inglese (lingua canonica) e fa upsert nella tabella sets
 *  2. Per ogni altra lingua nel DB, recupera i nomi tradotti e fa upsert in set_translations
 *
 * Idempotente: può essere eseguito più volte senza duplicati (usa updateOrCreate su tcgdex_id).
 */
class SyncSetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 3;

    public function handle(TcgdexService $tcgdex): void
    {
        Log::info('SyncSetsJob: avvio sincronizzazione set');

        // 1. Sincronizza i dati canonici (inglese)
        $enSets = $tcgdex->withLang('en')->fetchSets();

        if (empty($enSets)) {
            Log::warning('SyncSetsJob: nessun set ricevuto da TCGdex (en)');
            return;
        }

        $synced = 0;

        foreach ($enSets as $setData) {
            $tcgdexId = $setData['id'] ?? null;

            if (! $tcgdexId) {
                continue;
            }

            try {
                // Dettaglio completo del set per avere cardCount
                $detail = $tcgdex->withLang('en')->fetchSet($tcgdexId);

                // Upsert della serie
                $serieName   = $detail['serie']['name'] ?? $setData['serie']['name'] ?? null;
                $serieTcgId  = $detail['serie']['id']   ?? $setData['serie']['id']   ?? null;
                $seriesModel = null;

                if ($serieName) {
                    $serieLogoUrl = $detail['serie']['logo'] ?? $setData['serie']['logo'] ?? null;

                    if ($serieTcgId) {
                        // withTrashed: una serie nascosta dall'admin si aggiorna invece di violare l'unique
                        $seriesModel = Series::withTrashed()->updateOrCreate(
                            ['tcgdex_id' => $serieTcgId],
                            ['name' => $serieName, 'logo_url' => $serieLogoUrl]
                        );
                    } else {
                        // Fallback: usa il nome come chiave univoca per evitare duplicati
                        $seriesModel = Series::withTrashed()->firstOrCreate(
                            ['name' => $serieName, 'tcgdex_id' => null],
                            ['logo_url' => $serieLogoUrl]
                        );
                        // Aggiorna il logo se già esistente
                        if ($serieLogoUrl && ! $seriesModel->logo_url) {
                            $seriesModel->update(['logo_url' => $serieLogoUrl]);
                        }
                    }
                }

                Set::withTrashed()->updateOrCreate(
                    ['tcgdex_id' => $tcgdexId],
                    [
                        'name'                => $setData['name'] ?? $tcgdexId,
                        'series_id'           => $seriesModel?->id,
                        'card_count_official' => $detail['cardCount']['official'] ?? ($setData['cardCount']['official'] ?? 0),
                        'card_count_total'    => $detail['cardCount']['total'] ?? ($setData['cardCount']['total'] ?? 0),
                        'release_date'        => $detail['releaseDate'] ?? null,
                        'symbol_url'          => $detail['symbol'] ?? null,
                        'logo_url'            => $detail['logo'] ?? null,
                    ]
                );

                $synced++;
            } catch (TcgdexException $e) {
                throw $e;
            } catch (\Throwable $e) {
                Log::error("SyncSetsJob: errore su set {$tcgdexId} → {$e->getMessage()}");
            }
        }

        Log::info("SyncSetsJob: sincronizzati {$synced} set (en)");

        // 2. Sincronizza le traduzioni per le lingue presenti nel DB
        $languages = Language::whereNotNull('code')->get();

        foreach ($languages as $language) {
            // Codice di TCGdex (es. zh-CN → zh-cn); null se la lingua non è supportata
            $lang = TcgdexService::apiLang($language->code);

            // Salta l'inglese (già nella tabella principale) e le lingue non supportate
            if (! $lang || $lang === 'en') {
                continue;
            }

            $this->syncTranslations($tcgdex, $language, $lang);
        }

        Log::info('SyncSetsJob: completato');
    }

    private function syncTranslations(TcgdexService $tcgdex, Language $language, string $lang): void
    {
        $sets = $tcgdex->withLang($lang)->fetchSets();

        if (empty($sets)) {
            Log::warning("SyncSetsJob: nessun set ricevuto da TCGdex ({$lang})");
            return;
        }

        $count = 0;

        foreach ($sets as $setData) {
            $tcgdexId = $setData['id'] ?? null;
            $name     = $setData['name'] ?? null;

            if (! $tcgdexId || ! $name) {
                continue;
            }

            $set = Set::withTrashed()->where('tcgdex_id', $tcgdexId)->first();

            if (! $set) {
                continue;
            }

            try {
                DB::table('set_translations')->updateOrInsert(
                    ['set_id' => $set->id, 'language_id' => $language->id],
                    ['name' => $name, 'updated_at' => now(), 'created_at' => now()]
                );

                $count++;
            } catch (\Throwable $e) {
                Log::error("SyncSetsJob [{$lang}]: errore traduzione {$tcgdexId} → {$e->getMessage()}");
            }
        }

        Log::info("SyncSetsJob [{$lang}]: sincronizzate {$count} traduzioni");
    }
}
