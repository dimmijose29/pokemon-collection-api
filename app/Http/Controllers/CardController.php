<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardRequest;
use App\Http\Resources\CardResource;
use App\Models\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardController extends CatalogController
{
    protected string $model = Card::class;
    protected string $resource = CardResource::class;
    protected string $request = CardRequest::class;

    protected array $with = ['set.series', 'artist', 'rarity', 'cardType', 'translations.language', 'latestCardmarketPrice'];

    protected string $deletedMessage = 'Carta eliminata con successo';

    /**
     * Lista carte con supporto withTrashed, filtri multipli e paginazione opzionale.
     * Quando viene passato il parametro `page`, la risposta è paginata (meta incluso).
     * Senza `page` si restituisce l'array completo (compatibilità con SetCardsTable).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Card::with(['set.series', 'rarity', 'artist', 'cardType', 'translations', 'latestCardmarketPrice'])
            ->orderBy('set_id')
            ->orderByRaw('CAST(local_id AS UNSIGNED)')
            ->orderBy('local_id');

        if ($request->boolean('withTrashed')) {
            $query->withTrashed();
        }

        // --- Filtri ---
        if ($request->filled('set_id')) {
            $query->where('set_id', $request->integer('set_id'));
        }

        if ($request->filled('series_id')) {
            $query->whereHas('set', fn($q) => $q->where('series_id', $request->integer('series_id')));
        }

        if ($request->filled('rarity_id')) {
            $query->where('rarity_id', $request->integer('rarity_id'));
        }

        if ($request->filled('card_type_id')) {
            $query->where('card_type_id', $request->integer('card_type_id'));
        }

        if ($request->filled('artist_id')) {
            $query->where('artist_id', $request->integer('artist_id'));
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->string('stage'));
        }

        if ($request->filled('energy_type')) {
            $query->whereJsonContains('energy_types', $request->string('energy_type')->toString());
        }

        if ($request->filled('name')) {
            $name = $request->string('name')->toString();
            $query->whereHas('translations', fn($q) => $q->where('name', 'LIKE', "%{$name}%"));
        }

        if ($request->boolean('has_price')) {
            $query->whereHas('latestCardmarketPrice');
        }

        // --- Paginazione (solo se `page` è presente) ---
        if ($request->filled('page')) {
            // Limite superiore: senza, per_page arbitrario permette di scaricare tutto in una richiesta
            $perPage = min(max($request->integer('per_page', 48), 1), 100);
            $paginated = $query->paginate($perPage);

            return response()->json([
                'data' => CardResource::collection($paginated->items()),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ]);
        }

        return response()->json(CardResource::collection($query->get()));
    }
}
