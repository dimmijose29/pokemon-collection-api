<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Currency;
use App\Http\Requests\CurrencyRequest;
use App\Http\Resources\CurrencyResource;

class CurrencyController extends Controller
{
    /**
     * Restituisce tutte le valute.
     * Accetta il parametro ?withTrashed=true per includere i record eliminati.
     */
    public function index(Request $request)
    {
        $query = Currency::orderBy('code');

        if ($request->boolean('withTrashed')) {
            $query->withTrashed();
        }

        return response()->json(CurrencyResource::collection($query->get()));
    }

    /**
     * Crea una nuova valuta.
     */
    public function store(CurrencyRequest $request)
    {
        $currency = Currency::create($request->validated());

        return response()->json(new CurrencyResource($currency), 201);
    }

    /**
     * Mostra una singola valuta.
     */
    public function show(Currency $currency)
    {
        return response()->json(new CurrencyResource($currency));
    }

    /**
     * Aggiorna una valuta esistente.
     */
    public function update(CurrencyRequest $request, Currency $currency)
    {
        $currency->update($request->validated());

        return response()->json(new CurrencyResource($currency));
    }

    /**
     * Soft-delete di una valuta.
     */
    public function destroy(Currency $currency)
    {
        $currency->delete();

        return response()->json(['message' => 'Valuta eliminata con successo'], 200);
    }

    /**
     * Ripristina una valuta eliminata.
     */
    public function restore($currency)
    {
        $currency = Currency::withTrashed()->findOrFail($currency);
        $currency->restore();

        return response()->json(new CurrencyResource($currency));
    }
}
