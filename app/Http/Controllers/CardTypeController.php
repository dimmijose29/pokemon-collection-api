<?php

namespace App\Http\Controllers;

use App\Models\CardType;
use App\Http\Requests\CardTypeRequest;
use App\Http\Resources\CardTypeResource;

class CardTypeController extends Controller
{
    public function index()
    {
        return response()->json(CardTypeResource::collection(CardType::all()));
    }

    public function store(CardTypeRequest $request)
    {
        $cardType = CardType::create($request->validated());

        return response()->json(new CardTypeResource($cardType), 201);
    }

    public function show(CardType $cardType)
    {
        return response()->json(new CardTypeResource($cardType));
    }

    public function update(CardTypeRequest $request, CardType $cardType)
    {
        $cardType->update($request->validated());

        return response()->json(new CardTypeResource($cardType));
    }

    public function destroy(CardType $cardType)
    {
        $cardType->delete();

        return response()->json(['message' => 'Tipo di carta eliminato con successo'], 200);
    }

    public function restore($cardType)
    {
        $cardType  = CardType::withTrashed()->findOrFail($cardType);

        $cardType->restore();

        return response()->json(new CardTypeResource($cardType));
    }
}