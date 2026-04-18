<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Condition;
use App\Http\Requests\ConditionRequest;
use App\Http\Resources\ConditionResource;

class ConditionController extends Controller
{
    /**
     * Restituisce tutte le condizioni.
     * Accetta il parametro ?withTrashed=true per includere i record eliminati.
     */
    public function index(Request $request)
    {
        $query = Condition::orderBy('name');

        if ($request->boolean('withTrashed')) {
            $query->withTrashed();
        }

        return response()->json(ConditionResource::collection($query->get()));
    }

    /**
     * Crea una nuova condizione.
     */
    public function store(ConditionRequest $request)
    {
        $condition = Condition::create($request->validated());

        return response()->json(new ConditionResource($condition), 201);
    }

    /**
     * Mostra una singola condizione.
     */
    public function show(Condition $condition)
    {
        return response()->json(new ConditionResource($condition));
    }

    /**
     * Aggiorna una condizione esistente.
     */
    public function update(ConditionRequest $request, Condition $condition)
    {
        $condition->update($request->validated());

        return response()->json(new ConditionResource($condition));
    }

    /**
     * Soft-delete di una condizione.
     */
    public function destroy(Condition $condition)
    {
        $condition->delete();

        return response()->json(['message' => 'Condizione eliminata con successo'], 200);
    }

    /**
     * Ripristina una condizione eliminata.
     */
    public function restore($condition)
    {
        $condition = Condition::withTrashed()->findOrFail($condition);
        $condition->restore();

        return response()->json(new ConditionResource($condition));
    }
}
