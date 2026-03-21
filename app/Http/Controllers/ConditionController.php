<?php

namespace App\Http\Controllers;

use App\Models\Condition;
use App\Http\Requests\ConditionRequest;
use App\Http\Resources\ConditionResource;

class ConditionController extends Controller
{
    public function index()
    {
        return response()->json(ConditionResource::collection(Condition::all()));
    }

    public function store(ConditionRequest $request)
    {
        return response()->json(new ConditionResource(Condition::create($request->validated())), 201);
    }

    public function show(Condition $condition)
    {
        return response()->json(new ConditionResource($condition));
    }

    public function update(ConditionRequest $request, Condition $condition)
    {
        $condition->update($request->validated());

        return response()->json(new ConditionResource($condition));
    }

    public function destroy(Condition $condition)
    {
        $condition->delete();

        return response()->json(['message' => 'Condizione eliminata con successo'], 200);
    }

    public function restore($condition)
    {
        $condition  = Condition::withTrashed()->findOrFail($condition);

        $condition->restore();

        return response()->json(new ConditionResource($condition));
    }
}
