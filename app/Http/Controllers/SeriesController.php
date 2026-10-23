<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeriesRequest;
use App\Http\Resources\SeriesResource;
use App\Models\Series;

class SeriesController extends CatalogController
{
    protected string $model = Series::class;
    protected string $resource = SeriesResource::class;
    protected string $request = SeriesRequest::class;

    protected array $withCount = ['sets'];

    protected string $deletedMessage = 'Serie eliminata';
}
