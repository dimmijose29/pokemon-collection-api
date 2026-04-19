<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Series;
use App\Http\Requests\SeriesRequest;
use App\Http\Resources\SeriesResource;

class SeriesController extends Controller
{
    public function index(Request $request)
    {
        $query = Series::withCount('sets')->orderBy('name');
        if ($request->boolean('withTrashed')) $query->withTrashed();
        return response()->json(SeriesResource::collection($query->get()));
    }

    public function store(SeriesRequest $request)
    {
        $series = Series::create($request->validated());
        return response()->json(new SeriesResource($series->loadCount('sets')), 201);
    }

    public function show(Series $series)
    {
        return response()->json(new SeriesResource($series->loadCount('sets')));
    }

    public function update(SeriesRequest $request, Series $series)
    {
        $series->update($request->validated());
        return response()->json(new SeriesResource($series->loadCount('sets')));
    }

    public function destroy(Series $series)
    {
        $this->authorize('delete', $series);
        $series->delete();
        return response()->json(['message' => 'Serie eliminata'], 200);
    }

    public function restore($id)
    {
        $series = Series::withTrashed()->findOrFail($id);
        $this->authorize('restore', $series);
        $series->restore();
        return response()->json(new SeriesResource($series->loadCount('sets')));
    }
}
