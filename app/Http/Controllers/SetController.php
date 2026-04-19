<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Set;
use App\Http\Requests\SetRequest;
use App\Http\Resources\SetResource;

class SetController extends Controller
{
    /**
     * Lista set con supporto withTrashed e filtro serie.
     */
    public function index(Request $request)
    {
        $query = Set::with('series')->orderBy('release_date', 'desc')->orderBy('name');

        if ($request->boolean('withTrashed')) {
            $query->withTrashed();
        }

        if ($request->filled('series_id')) {
            $query->where('series_id', $request->integer('series_id'));
        }

        return response()->json(SetResource::collection($query->get()));
    }

    /**
     * Dettaglio set con traduzioni e serie caricate.
     */
    public function show(Set $set)
    {
        $set->load(['translations.language', 'series']);

        return response()->json(new SetResource($set));
    }

    public function store(SetRequest $request)
    {
        $set = Set::create($request->validated());
        $set->load('series');

        return response()->json(new SetResource($set), 201);
    }

    public function update(SetRequest $request, Set $set)
    {
        $set->update($request->validated());
        $set->load('series');

        return response()->json(new SetResource($set));
    }

    public function destroy(Set $set)
    {
        $set->delete();

        return response()->json(['message' => 'Set eliminato con successo']);
    }

    public function restore($set)
    {
        $set = Set::withTrashed()->findOrFail($set);
        $set->restore();
        $set->load('series');

        return response()->json(new SetResource($set));
    }
}
