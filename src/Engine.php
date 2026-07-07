<?php

namespace DirectoryTree\OpenSearchScoutDriver;

use DirectoryTree\OpenSearchAdapter\Documents\DocumentManagerInterface;
use DirectoryTree\OpenSearchAdapter\Indices\IndexBlueprint;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManagerInterface;
use DirectoryTree\OpenSearchAdapter\Search\Hit;
use DirectoryTree\OpenSearchAdapter\Search\SearchResponse;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator as IlluminateCursorPaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine as ScoutEngine;
use stdClass;

/**
 * Laravel Scout engine backed by OpenSearch.
 */
class Engine extends ScoutEngine
{
    /**
     * Create a new OpenSearch Scout engine instance.
     */
    public function __construct(
        protected ModelFactoryInterface $modelFactory,
        protected IndexManagerInterface $indexManager,
        protected DocumentManagerInterface $documentManager,
        protected DocumentFactoryInterface $documentFactory,
        protected SearchRequestFactoryInterface $searchRequestFactory,
        protected bool $refreshDocuments = false,
    ) {}

    /**
     * Update the given models in the index.
     */
    public function update($models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $index = $models->first()->searchableAs();

        $documents = $this->documentFactory->makeFromModels($models);

        $this->documentManager->index($index, $documents->all(), $this->refreshDocuments);
    }

    /**
     * Delete the given models from the index.
     */
    public function delete($models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $index = $models->first()->searchableAs();

        $documentIds = $models->map(fn (Model $model) => (string) $model->getScoutKey())->all();

        $this->documentManager->delete($index, $documentIds, $this->refreshDocuments);
    }

    /**
     * Perform the given search.
     */
    public function search(Builder $builder): SearchResponse
    {
        $searchRequest = $this->searchRequestFactory->makeFromBuilder($builder);

        return $this->documentManager->search($searchRequest->indexName(), $searchRequest->request());
    }

    /**
     * Perform the given paginated search.
     */
    public function paginate(Builder $builder, $perPage, $page): SearchResponse
    {
        $searchRequest = $this->searchRequestFactory->makeFromBuilder($builder, [
            'perPage' => (int) $perPage,
            'page' => (int) $page,
        ]);

        return $this->documentManager->search($searchRequest->indexName(), $searchRequest->request());
    }

    /**
     * Cursor paginate the given search using OpenSearch search_after values.
     */
    public function cursorPaginate(Builder $builder, $perPage = null, string $cursorName = 'cursor', Cursor|string|null $cursor = null): CursorPaginator
    {
        $perPage = (int) ($perPage ?: $builder->model->getPerPage());

        $cursor = $this->resolveCursor($cursor, $cursorName);

        $searchRequest = $this->searchRequestFactory->makeFromBuilder($builder, [
            'perPage' => $perPage + 1,
            'reversed' => $cursor?->pointsToPreviousItems() ?? false,
            'searchAfter' => $cursor?->parameter(CursorPaginator::SEARCH_AFTER_PARAMETER),
        ]);

        if (empty($searchRequest->request()->toArray()['body']['sort'] ?? [])) {
            throw new InvalidArgumentException('OpenSearch cursor pagination requires at least one explicit sort.');
        }

        $response = $this->documentManager->search($searchRequest->indexName(), $searchRequest->request());

        return new CursorPaginator(
            $this->map($builder, $response, $builder->model),
            $perPage,
            $cursor,
            [
                'cursorName' => $cursorName,
                'path' => Paginator::resolveCurrentPath(),
                'parameters' => [CursorPaginator::SEARCH_AFTER_PARAMETER],
                'searchAfter' => $this->searchAfterValuesByDocumentId($response),
            ],
        );
    }

    /**
     * Get the primary keys from the search results.
     */
    public function mapIds($results): BaseCollection
    {
        return collect($results->hits())->map(fn (Hit $hit) => $hit->document()->id());
    }

    /**
     * Map the search results to models.
     */
    public function map(Builder $builder, $results, $model): EloquentCollection
    {
        return $this->modelFactory->makeFromSearchResponse($results, $builder);
    }

    /**
     * Lazily map the search results to models.
     */
    public function lazyMap(Builder $builder, $results, $model): LazyCollection
    {
        return $this->modelFactory->makeLazyFromSearchResponse($results, $builder);
    }

    /**
     * Get the total count from the search results.
     */
    public function getTotalCount($results): ?int
    {
        return $results->total();
    }

    /**
     * Resolve the cursor from the current request or explicit value.
     */
    protected function resolveCursor(Cursor|string|null $cursor, string $cursorName): ?Cursor
    {
        if ($cursor instanceof Cursor) {
            return $cursor;
        }

        if (is_string($cursor)) {
            return Cursor::fromEncoded($cursor);
        }

        return IlluminateCursorPaginator::resolveCurrentCursor($cursorName);
    }

    /**
     * Get hit sort values keyed by document ID.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function searchAfterValuesByDocumentId(SearchResponse $response): array
    {
        $values = [];

        foreach ($response->hits() as $hit) {
            $values[$hit->document()->id()] = $hit->sort();
        }

        return $values;
    }

    /**
     * Remove all model records from the index.
     */
    public function flush($model): void
    {
        $index = $model->searchableAs();

        $query = ['match_all' => new stdClass];

        $this->documentManager->deleteByQuery($index, $query, $this->refreshDocuments);
    }

    /**
     * Create an index.
     */
    public function createIndex($name, array $options = []): void
    {
        if (isset($options['primaryKey'])) {
            throw new InvalidArgumentException('It is not possible to change the primary key name.');
        }

        $this->indexManager->create(new IndexBlueprint($name));
    }

    /**
     * Delete an index.
     */
    public function deleteIndex($name): void
    {
        $this->indexManager->delete($name);
    }
}
