<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper per l'API REST di TCGdex.
 *
 * La lingua viene passata nel costruttore e tutti gli endpoint
 * verranno chiamati nella lingua specificata.
 *
 * Documentazione: https://tcgdex.dev/
 * Base URL:       https://api.tcgdex.net/v2/{lang}/
 */
class TcgdexService
{
    private string $baseUrl;

    /**
     * Lingue supportate da TCGdex. Usare i codici ISO 639-1.
     */
    public const SUPPORTED_LANGS = ['en', 'it', 'fr', 'de', 'es', 'pt', 'ja', 'ko', 'zh-CN', 'zh-TW'];

    public function __construct(private string $lang = 'en')
    {
        $this->baseUrl = "https://api.tcgdex.net/v2/{$lang}";
    }

    /**
     * Restituisce tutti i set disponibili per la lingua corrente.
     *
     * @return array<int, array>
     */
    public function fetchSets(): array
    {
        return $this->get('/sets') ?? [];
    }

    /**
     * Restituisce il dettaglio di un set con la lista delle sue carte.
     *
     * @return array|null
     */
    public function fetchSet(string $tcgdexId): ?array
    {
        return $this->get("/sets/{$tcgdexId}");
    }

    /**
     * Restituisce il dettaglio di una carta specifica.
     *
     * @return array|null
     */
    public function fetchCard(string $tcgdexId): ?array
    {
        return $this->get("/cards/{$tcgdexId}");
    }

    /**
     * Restituisce tutte le carte di un set (lista leggera).
     * Le carte sono embedded nell'oggetto set — TCGdex non espone /sets/{id}/cards.
     *
     * @return array<int, array>
     */
    public function fetchCardsForSet(string $setTcgdexId): array
    {
        $set = $this->get("/sets/{$setTcgdexId}");

        return $set['cards'] ?? [];
    }

    /**
     * Restituisce tutte le rarità disponibili per la lingua corrente.
     *
     * @return array<int, string>
     */
    public function fetchRarities(): array
    {
        return $this->get('/rarities') ?? [];
    }

    /**
     * Restituisce tutte le categorie di carte (Pokémon, Trainer, Energy).
     *
     * @return array<int, string>
     */
    public function fetchCategories(): array
    {
        return $this->get('/categories') ?? [];
    }

    /**
     * Restituisce tutti gli illustratori presenti nel catalogo TCGdex.
     *
     * @return array<int, string>
     */
    public function fetchIllustrators(): array
    {
        return $this->get('/illustrators') ?? [];
    }

    /**
     * Cambia la lingua per la richiesta successiva (ritorna un clone).
     */
    public function withLang(string $lang): static
    {
        return new static($lang);
    }

    /**
     * Esegue una GET sull'API e ritorna il JSON decodificato o null in caso di errore.
     */
    private function get(string $path): array|null
    {
        try {
            $response = Http::timeout(30)
                ->get($this->baseUrl . $path);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("TCGdex [{$this->lang}] {$path} → HTTP {$response->status()}");

            return null;
        } catch (\Throwable $e) {
            Log::error("TCGdex [{$this->lang}] {$path} → {$e->getMessage()}");

            return null;
        }
    }
}
