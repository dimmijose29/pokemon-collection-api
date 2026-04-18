<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Language;
use App\Http\Requests\LanguageRequest;
use App\Http\Resources\LanguageResource;

class LanguageController extends Controller
{
    /**
     * Restituisce tutte le lingue.
     * Accetta il parametro ?withTrashed=true per includere i record eliminati.
     */
    public function index(Request $request)
    {
        $query = Language::orderBy('name');

        if ($request->boolean('withTrashed')) {
            $query->withTrashed();
        }

        return response()->json(LanguageResource::collection($query->get()));
    }

    /**
     * Crea una nuova lingua.
     */
    public function store(LanguageRequest $request)
    {
        $language = Language::create($request->validated());

        return response()->json(new LanguageResource($language), 201);
    }

    /**
     * Mostra una singola lingua.
     */
    public function show(Language $language)
    {
        return response()->json(new LanguageResource($language));
    }

    /**
     * Aggiorna una lingua esistente.
     */
    public function update(LanguageRequest $request, Language $language)
    {
        $language->update($request->validated());

        return response()->json(new LanguageResource($language));
    }

    /**
     * Soft-delete di una lingua.
     */
    public function destroy(Language $language)
    {
        $language->delete();

        return response()->json(['message' => 'Lingua eliminata con successo'], 200);
    }

    /**
     * Ripristina una lingua eliminata.
     */
    public function restore($language)
    {
        $language = Language::withTrashed()->findOrFail($language);
        $language->restore();

        return response()->json(new LanguageResource($language));
    }
}
