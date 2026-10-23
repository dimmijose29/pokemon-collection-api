<?php

namespace App\Http\Controllers;

use App\Http\Requests\CurrencyRequest;
use App\Http\Resources\CurrencyResource;
use App\Models\Currency;

class CurrencyController extends CatalogController
{
    protected string $model = Currency::class;
    protected string $resource = CurrencyResource::class;
    protected string $request = CurrencyRequest::class;

    protected string $orderBy = 'code';

    protected string $deletedMessage = 'Valuta eliminata con successo';
}
