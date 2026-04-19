<?php

namespace App\Console\Commands;

use App\Jobs\SyncCardsJob;
use App\Models\Set;
use Illuminate\Console\Command;

class SyncCardsCommand extends Command
{
    protected $signature   = 'sync:cards
                                {set? : tcgdex_id del set specifico (es. swsh1). Ometti per sincronizzare tutti i set.}
                                {--queue : Dispatcha i job sulla coda invece di eseguirli in modo sincrono}
                                {--only-new : Salta i set che hanno già carte nel DB}';

    protected $description = 'Sincronizza le carte Pokémon da TCGdex. Specificare un set o sincronizzare tutti.';

    public function handle(): int
    {
        $setId = $this->argument('set');

        if ($setId) {
            $this->syncSet($setId);
        } else {
            $this->syncAllSets();
        }

        return Command::SUCCESS;
    }

    private function syncSet(string $tcgdexId): void
    {
        if ($this->option('queue')) {
            SyncCardsJob::dispatch($tcgdexId);
            $this->info("SyncCardsJob dispatchato per il set [{$tcgdexId}].");
        } else {
            $this->info("Sincronizzazione carte per set [{$tcgdexId}]...");
            app(SyncCardsJob::class, ['setTcgdexId' => $tcgdexId])
                ->handle(app(\App\Services\TcgdexService::class));
            $this->info("Completato: [{$tcgdexId}].");
        }
    }

    private function syncAllSets(): void
    {
        $query = Set::whereNull('deleted_at');

        // --only-new: salta i set che hanno già almeno una carta nel DB
        if ($this->option('only-new')) {
            $query->whereDoesntHave('cards');
            $this->info('Modalità --only-new: verranno saltati i set già sincronizzati.');
        }

        $sets = $query->pluck('tcgdex_id');

        if ($sets->isEmpty()) {
            $this->warn('Nessun set da sincronizzare. Tutti i set hanno già carte, oppure il DB è vuoto.');
            return;
        }

        $this->info("Sincronizzazione carte per {$sets->count()} set...");
        $bar = $this->output->createProgressBar($sets->count());
        $bar->start();

        foreach ($sets as $tcgdexId) {
            if ($this->option('queue')) {
                SyncCardsJob::dispatch($tcgdexId);
            } else {
                app(SyncCardsJob::class, ['setTcgdexId' => $tcgdexId])
                    ->handle(app(\App\Services\TcgdexService::class));
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sincronizzazione completata.');
    }
}
