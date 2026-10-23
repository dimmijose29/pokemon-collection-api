<?php

namespace App\Http\Controllers;

use App\Http\Requests\LanguageRequest;
use App\Http\Resources\LanguageResource;
use App\Models\Language;

class LanguageController extends CatalogController
{
    protected string $model = Language::class;
    protected string $resource = LanguageResource::class;
    protected string $request = LanguageRequest::class;

    protected string $deletedMessage = 'Lingua eliminata con successo';
}
