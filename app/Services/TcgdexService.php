<?php

namespace App\Services;

use App\Exceptions\TcgdexException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Wrapper per l'API REST di TCGdex.
 *
 * La lingua viene passata nel costruttore e tutti gli endpoint
 * verranno chiamati nella lingua specificata.
 *
 * Documentazione: https://tcgdex.dev/
 * Base URL:       https://api.tcgdex.net/v2/{lang}/
 *
 * Errori: una risorsa inesistente (404) restituisce null o un array vuoto;
 * errori di rete, 429 e 5xx vengono ritentati e, se persistono, lanciano TcgdexException.
 */
class TcgdexService
{
    /**
     * Lingue supportate da TCGdex, con i codici che usa nell'URL (minuscoli: "zh-CN" risponde errore).
     */
    public const SUPPORTED_LANGS = ['en', 'it', 'fr', 'de', 'es', 'pt', 'ja', 'ko', 'zh-cn', 'zh-tw'];

    private string $lang;

    public function __construct(string $lang = 'en')
    {
        $this->lang = self::apiLang($lang) ?? 'en';
    }

    /**
     * Codice lingua di TCGdex per un codice della tabella languages (es. "zh-CN" → "zh-cn"),
     * null se TCGdex non la supporta.
     */
    public static function apiLang(string $code): ?string
    {
        $lang = strtolower(trim($code));

        return in_array($lang, self::SUPPORTED_LANGS, true) ? $lang : null;
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
     * Restituisce il dettaglio di un set, con la lista delle sue carte (id, localId, nome nella lingua).
     */
    public function fetchSet(string $tcgdexId): ?array
    {
        return $this->get("/sets/{$tcgdexId}");
    }

    /**
     * Restituisce il dettaglio di una carta specifica (rarità, illustratore, prezzi...).
     */
    public function fetchCard(string $tcgdexId): ?array
    {
        return $this->get("/cards/{$tcgdexId}");
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

    public function lang(): string
    {
        return $this->lang;
    }

    /**
     * GET con timeout e retry: null per 404, eccezione per gli errori che persistono.
     */
    private function get(string $path): ?array
    {
        try {
            $response = $this->client()->get("/{$this->lang}{$path}");
        } catch (ConnectionException $e) {
            throw new TcgdexException("TCGdex [{$this->lang}] {$path} non raggiungibile: {$e->getMessage()}", 0, $e);
        }

        if ($response->notFound()) {
            return null;
        }

        if ($response->failed()) {
            throw new TcgdexException("TCGdex [{$this->lang}] {$path} → HTTP {$response->status()}");
        }

        return $response->json();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('services.tcgdex.url'), '/'))
            ->acceptJson()
            ->withUserAgent(config('app.name') . ' (+' . config('app.url') . ')')
            ->connectTimeout(config('services.tcgdex.connect_timeout'))
            ->timeout(config('services.tcgdex.timeout'))
            // Ritenta solo ciò che può risolversi da solo: rete, rate limit, errori del server
            ->retry(
                config('services.tcgdex.retries'),
                fn (int $attempt) => $attempt * 1000,
                fn (\Throwable $e) => $e instanceof ConnectionException
                    || ($e instanceof RequestException && ($e->response->status() === 429 || $e->response->serverError())),
                // la risposta finale la valuta get(): 404 → null, altri errori → eccezione
                throw: false,
            );
    }
}
