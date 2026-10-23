<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArtistRequest;
use App\Http\Resources\ArtistResource;
use App\Models\Artist;

class ArtistController extends CatalogController
{
    protected string $model = Artist::class;
    protected string $resource = ArtistResource::class;
    protected string $request = ArtistRequest::class;

    protected string $deletedMessage = 'Artista eliminato con successo';
}
