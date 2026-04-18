<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CardType;
use App\Http\Requests\CardTypeRequest;
use App\Http\Resources\CardTypeResource;

class CardTypeController extends Controller
{
    /**
     * Restituisce tutti i tipi di carta.
     * Accetta il parametro ?withTrashed=true per includere i record eliminati.
     */
    public function index(Request $request)
    {
        $query = CardType::orderBy('name');

        if ($request->boolean('withTrashed')) {
            $query->withTrashed();
        }

        return response()->json(CardTypeResource::collection($query->get()));
    }

    /**
     * Crea un nuovo tipo di carta.
     */
    public function store(CardTypeRequest $request)
    {
        $cardType = CardType::create($request->validated());

        return response()->json(new CardTypeResource($cardType), 201);
    }

    /**
     * Mostra un singolo tipo di carta.
     */
    public function show(CardType $cardType)
    {
        return response()->json(new CardTypeResource($cardType));
    }

    /**
     * Aggiorna un tipo di carta esistente.
     */
    public function update(CardTypeRequest $request, CardType $cardType)
    {
        $cardType->update($request->validated());

        return response()->json(new CardTypeResource($cardType));
    }

    /**
     * Soft-delete di un tipo di carta.
     */
    public function destroy(CardType $cardType)
    {
        $cardType->delete();

        return response()->json(['message' => 'Tipo di carta eliminato con successo'], 200);
    }

    /**
     * Ripristina un tipo di carta eliminato.
     * Usa il parametro raw $cardType perché il model binding esclude i soft-deleted.
     */
    public function restore($cardType)
    {
        $cardType = CardType::withTrashed()->findOrFail($cardType);
        $cardType->restore();

        return response()->json(new CardTypeResource($cardType));
    }
}
