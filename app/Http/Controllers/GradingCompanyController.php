<?php

namespace App\Http\Controllers;

use App\Models\GradingCompany;
use App\Http\Requests\GradingCompanyRequest;
use App\Http\Resources\GradingCompanyResource;

class GradingCompanyController extends Controller
{
    public function index()
    {
        return response()->json(GradingCompanyResource::collection(GradingCompany::all()));
    }

    public function store(GradingCompanyRequest $request)
    {
        $gradingCompany = GradingCompany::create($request->validated());

        return response()->json(new GradingCompanyResource($gradingCompany), 201);
    }

    public function show(GradingCompany $gradingCompany)
    {
        return response()->json(new GradingCompanyResource($gradingCompany));
    }

    public function update(GradingCompanyRequest $request, GradingCompany $gradingCompany)
    {
        $gradingCompany->update($request->validated());

        return response()->json(new GradingCompanyResource($gradingCompany));
    }

    public function destroy(GradingCompany $gradingCompany)
    {
        $gradingCompany->delete();

        return response()->json(['message' => 'Compagnia di valutazione eliminata con successo'], 200);
    }

    public function restore($gradingCompany)
    {
        $gradingCompany  = GradingCompany::withTrashed()->findOrFail($gradingCompany);

        $gradingCompany->restore();

        return response()->json(new GradingCompanyResource($gradingCompany));
    }
}