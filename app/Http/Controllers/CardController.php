<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use App\Http\Requests\CardRequest;
use App\Http\Resources\CardResource;

class CardController extends Controller
{
    /**
     * Lista carte con supporto withTrashed, filtri multipli e paginazione opzionale.
     * Quando viene passato il parametro `page`, la risposta è paginata (meta incluso).
     * Senza `page` si restituisce l'array completo (compatibilità con SetCardsTable).
     */
    public function index(Request $request)
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
            $perPage = $request->integer('per_page', 48);
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

    /**
     * Dettaglio carta con tutte le relazioni e traduzioni.
     */
    public function show(Card $card)
    {
        $card->load(['set.series', 'artist', 'rarity', 'cardType', 'translations.language', 'latestCardmarketPrice']);

        return response()->json(new CardResource($card));
    }

    public function store(CardRequest $request)
    {
        $card = Card::create($request->validated());

        return response()->json(new CardResource($card), 201);
    }

    public function update(CardRequest $request, Card $card)
    {
        $card->update($request->validated());

        return response()->json(new CardResource($card));
    }

    public function destroy(Card $card)
    {
        $card->delete();

        return response()->json(['message' => 'Carta eliminata con successo']);
    }

    public function restore($card)
    {
        $card = Card::withTrashed()->findOrFail($card);
        $card->restore();

        return response()->json(new CardResource($card));
    }
}
