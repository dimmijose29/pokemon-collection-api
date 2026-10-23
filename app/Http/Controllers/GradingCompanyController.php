<?php

namespace App\Http\Controllers;

use App\Http\Requests\GradingCompanyRequest;
use App\Http\Resources\GradingCompanyResource;
use App\Models\GradingCompany;

class GradingCompanyController extends CatalogController
{
    protected string $model = GradingCompany::class;
    protected string $resource = GradingCompanyResource::class;
    protected string $request = GradingCompanyRequest::class;

    protected string $deletedMessage = 'Compagnia di grading eliminata con successo';
}
