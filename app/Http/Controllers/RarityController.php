<?php

namespace App\Http\Controllers;

use App\Http\Requests\RarityRequest;
use App\Http\Resources\RarityResource;
use App\Models\Rarity;

class RarityController extends CatalogController
{
    protected string $model = Rarity::class;
    protected string $resource = RarityResource::class;
    protected string $request = RarityRequest::class;

    protected string $deletedMessage = 'Rarità eliminata con successo';
}
