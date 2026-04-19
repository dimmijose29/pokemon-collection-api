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
        $query = Set::orderBy('release_date', 'desc')->orderBy('name');

        if ($request->boolean('withTrashed')) {
            $query->withTrashed();
        }

        if ($request->filled('series')) {
            $query->where('series', $request->string('series'));
        }

        return response()->json(SetResource::collection($query->get()));
    }

    /**
     * Dettaglio set con traduzioni caricate.
     */
    public function show(Set $set)
    {
        $set->load(['translations.language']);

        return response()->json(new SetResource($set));
    }

    public function store(SetRequest $request)
    {
        $set = Set::create($request->validated());

        return response()->json(new SetResource($set), 201);
    }

    public function update(SetRequest $request, Set $set)
    {
        $set->update($request->validated());

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

        return response()->json(new SetResource($set));
    }
}
