<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Http\Requests\ArtistRequest;
use App\Http\Resources\ArtistResource;

class ArtistController extends Controller
{
    public function index()
    {
        return response()->json(ArtistResource::collection(Artist::all()));
    }

    public function store(ArtistRequest $request)
    {
        $artist = Artist::create($request->validated());

        return response()->json(new ArtistResource($artist), 201);
    }

    public function show(Artist $artist)
    {
        return response()->json(new ArtistResource($artist));
    }

    public function update(ArtistRequest $request, Artist $artist)
    {
        $artist->update($request->validated());

        return response()->json(new ArtistResource($artist));
    }

    public function destroy(Artist $artist)
    {
        $artist->delete();

        return response()->json(['message' => 'Artista eliminato con successo'], 200);
    }

    public function restore($artist)
    {
        $artist  = Artist::withTrashed()->findOrFail($artist);

        $artist->restore();

        return response()->json(new ArtistResource($artist));
    }
}