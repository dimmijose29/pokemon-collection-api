<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CRUD standard delle entità di catalogo (index, show, store, update, destroy, restore).
 *
 * Ogni controller concreto dichiara modello, resource e FormRequest; le differenze
 * (ordinamento, relazioni, filtri) passano dalle proprietà e dagli hook qui sotto.
 * L'autorizzazione è sulle rotte (middleware `admin` sulle scritture), vedi routes/catalog.php.
 */
abstract class CatalogController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    /** @var class-string<JsonResource> */
    protected string $resource;

    /** @var class-string<\Illuminate\Foundation\Http\FormRequest> */
    protected string $request;

    /** Colonna di ordinamento della lista. */
    protected string $orderBy = 'name';

    /** Relazioni caricate nelle risposte su un singolo record. */
    protected array $with = [];

    /** Conteggi (withCount) caricati in lista e sul singolo record. */
    protected array $withCount = [];

    /** Messaggio restituito dopo il soft-delete. */
    protected string $deletedMessage = 'Elemento eliminato con successo';

    public function index(Request $request): JsonResponse
    {
        $query = $this->indexQuery($request);

        if ($request->boolean('withTrashed')) {
            $query->withTrashed();
        }

        return response()->json($this->resource::collection($query->get()));
    }

    public function show(string $id): JsonResponse
    {
        $record = $this->model::findOrFail($id);

        return $this->respond($record->load($this->showRelations()));
    }

    public function store(): JsonResponse
    {
        $record = $this->model::create($this->validated());

        return $this->respond($record->load($this->with), 201);
    }

    public function update(string $id): JsonResponse
    {
        $record = $this->model::findOrFail($id);
        $record->update($this->validated());

        return $this->respond($record->load($this->with));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->model::findOrFail($id)->delete();

        return response()->json(['message' => $this->deletedMessage]);
    }

    public function restore(string $id): JsonResponse
    {
        $record = $this->model::withTrashed()->findOrFail($id);
        $record->restore();

        return $this->respond($record->load($this->with));
    }

    /** Query base della lista: da estendere per filtri o ordinamenti diversi. */
    protected function indexQuery(Request $request): Builder
    {
        return $this->model::query()
            ->withCount($this->withCount)
            ->orderBy($this->orderBy);
    }

    /** Relazioni del dettaglio: di default le stesse delle altre risposte. */
    protected function showRelations(): array
    {
        return $this->with;
    }

    /** Risolvere la FormRequest dal container esegue validazione (e authorize). */
    protected function validated(): array
    {
        return app($this->request)->validated();
    }

    private function respond(Model $record, int $status = 200): JsonResponse
    {
        if ($this->withCount) {
            $record->loadCount($this->withCount);
        }

        return response()->json(new $this->resource($record), $status);
    }
}
