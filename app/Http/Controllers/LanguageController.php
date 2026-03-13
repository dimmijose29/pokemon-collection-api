<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Http\Requests\LanguageRequest;
use App\Http\Resources\LanguageResource;

class LanguageController extends Controller
{
    public function index()
    {
        return response()->json(LanguageResource::collection(Language::all()));
    }

    public function store(LanguageRequest $request)
    {
        return response()->json(new LanguageResource(Language::create($request->validated())), 201);
    }

    public function show(Language $language)
    {
        return response()->json(new LanguageResource($language));
    }

    public function update(LanguageRequest $request, Language $language)
    {
        $language->update($request->validated());

        return response()->json(new LanguageResource($language));
    }

    public function destroy(Language $language)
    {
        $language->delete();

        return response()->json(['message' => 'Lingua eliminata con successo'], 200);
    }

    public function restore($language)
    {
        $language  = Language::withTrashed()->findOrFail($language);

        $language->restore();

        return response()->json(new LanguageResource($language));
    }
}
