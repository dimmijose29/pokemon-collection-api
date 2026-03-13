<?php

namespace App\Http\Controllers;

use App\Models\Rarity;
use App\Http\Requests\RarityRequest;
use App\Http\Resources\RarityResource;

class RarityController extends Controller
{
    public function index()
    {
        return response()->json(RarityResource::collection(Rarity::all()));
    }

    public function store(RarityRequest $request)
    {
        $rarity = Rarity::create($request->validated());

        return response()->json(new RarityResource($rarity), 201);
    }

    public function show(Rarity $rarity)
    {
        return response()->json(new RarityResource($rarity));
    }

    public function update(RarityRequest $request, Rarity $rarity)
    {
        $rarity->update($request->validated());

        return response()->json(new RarityResource($rarity));
    }

    public function destroy(Rarity $rarity)
    {
        $rarity->delete();

        return response()->json(['message' => 'Rarità eliminata con successo'], 200);
    }

    public function restore($rarity)
    {
        $rarity  = Rarity::withTrashed()->findOrFail($rarity);

        $rarity->restore();

        return response()->json(new RarityResource($rarity));
    }
}