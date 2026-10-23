<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetRequest;
use App\Http\Resources\SetResource;
use App\Models\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SetController extends CatalogController
{
    protected string $model = Set::class;
    protected string $resource = SetResource::class;
    protected string $request = SetRequest::class;

    protected array $with = ['series'];

    protected string $deletedMessage = 'Set eliminato con successo';

    /**
     * Lista dal più recente, con filtro opzionale per serie.
     */
    protected function indexQuery(Request $request): Builder
    {
        $query = Set::with('series')->orderBy('release_date', 'desc')->orderBy('name');

        if ($request->filled('series_id')) {
            $query->where('series_id', $request->integer('series_id'));
        }

        return $query;
    }

    /** Il dettaglio include anche le traduzioni. */
    protected function showRelations(): array
    {
        return ['translations.language', 'series'];
    }
}
