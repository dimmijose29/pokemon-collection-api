<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use App\Http\Requests\CardRequest;
use App\Http\Resources\CardResource;

class CardController extends Controller
{
    /**
     * Lista carte con supporto withTrashed e filtro per set.
     */
    public function index(Request $request)
    {
        $query = Card::with(['set', 'rarity', 'artist', 'cardType', 'translations', 'latestCardmarketPrice'])
            ->orderBy('set_id')
            ->orderByRaw('CAST(local_id AS UNSIGNED)')
            ->orderBy('local_id');

        if ($request->boolean('withTrashed')) {
            $query->withTrashed();
        }

        if ($request->filled('set_id')) {
            $query->where('set_id', $request->integer('set_id'));
        }

        if ($request->filled('rarity_id')) {
            $query->where('rarity_id', $request->integer('rarity_id'));
        }

        return response()->json(CardResource::collection($query->get()));
    }

    /**
     * Dettaglio carta con tutte le relazioni e traduzioni.
     */
    public function show(Card $card)
    {
        $card->load(['set', 'artist', 'rarity', 'cardType', 'translations.language']);

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
