<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConditionRequest;
use App\Http\Resources\ConditionResource;
use App\Models\Condition;

class ConditionController extends CatalogController
{
    protected string $model = Condition::class;
    protected string $resource = ConditionResource::class;
    protected string $request = ConditionRequest::class;

    protected string $deletedMessage = 'Condizione eliminata con successo';
}
