<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GradingCompany;
use App\Http\Requests\GradingCompanyRequest;
use App\Http\Resources\GradingCompanyResource;

class GradingCompanyController extends Controller
{
    /**
     * Restituisce tutte le compagnie di grading.
     * Accetta il parametro ?withTrashed=true per includere i record eliminati.
     */
    public function index(Request $request)
    {
        $query = GradingCompany::orderBy('name');

        if ($request->boolean('withTrashed')) {
            $query->withTrashed();
        }

        return response()->json(GradingCompanyResource::collection($query->get()));
    }

    /**
     * Crea una nuova compagnia di grading.
     */
    public function store(GradingCompanyRequest $request)
    {
        $gradingCompany = GradingCompany::create($request->validated());

        return response()->json(new GradingCompanyResource($gradingCompany), 201);
    }

    /**
     * Mostra una singola compagnia di grading.
     */
    public function show(GradingCompany $gradingCompany)
    {
        return response()->json(new GradingCompanyResource($gradingCompany));
    }

    /**
     * Aggiorna una compagnia di grading esistente.
     */
    public function update(GradingCompanyRequest $request, GradingCompany $gradingCompany)
    {
        $gradingCompany->update($request->validated());

        return response()->json(new GradingCompanyResource($gradingCompany));
    }

    /**
     * Soft-delete di una compagnia di grading.
     */
    public function destroy(GradingCompany $gradingCompany)
    {
        $gradingCompany->delete();

        return response()->json(['message' => 'Compagnia di grading eliminata con successo'], 200);
    }

    /**
     * Ripristina una compagnia di grading eliminata.
     */
    public function restore($gradingCompany)
    {
        $gradingCompany = GradingCompany::withTrashed()->findOrFail($gradingCompany);
        $gradingCompany->restore();

        return response()->json(new GradingCompanyResource($gradingCompany));
    }
}
