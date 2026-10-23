<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardTypeRequest;
use App\Http\Resources\CardTypeResource;
use App\Models\CardType;

class CardTypeController extends CatalogController
{
    protected string $model = CardType::class;
    protected string $resource = CardTypeResource::class;
    protected string $request = CardTypeRequest::class;

    protected string $deletedMessage = 'Tipo di carta eliminato con successo';
}
