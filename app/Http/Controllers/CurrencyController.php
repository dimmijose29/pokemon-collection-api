<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Http\Requests\CurrencyRequest;
use App\Http\Resources\CurrencyResource;

class CurrencyController extends Controller
{
    public function index()
    {
        return response()->json(CurrencyResource::collection(Currency::all()));
    }

    public function store(CurrencyRequest $request)
    {
        return response()->json(new CurrencyResource(Currency::create($request->validated())), 201);
    }

    public function show(Currency $currency)
    {
        return response()->json(new CurrencyResource($currency));
    }

    public function update(CurrencyRequest $request, Currency $currency)
    {
        $currency->update($request->validated());

        return response()->json(new CurrencyResource($currency));
    }

    public function destroy(Currency $currency)
    {
        $currency->delete();

        return response()->json(['message' => 'Valuta eliminata con successo'], 200);
    }

    public function restore($currency)
    {
        $currency  = Currency::withTrashed()->findOrFail($currency);

        $currency->restore();

        return response()->json(new CurrencyResource($currency));
    }
}
