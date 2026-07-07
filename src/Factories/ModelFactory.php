<?php

namespace DirectoryTree\OpenSearchScoutDriver\Factories;

use DirectoryTree\OpenSearchAdapter\Search\Hit;
use DirectoryTree\OpenSearchAdapter\Search\SearchResponse;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;

/**
 * Creates Scout model results from OpenSearch responses.
 */
class ModelFactory implements ModelFactoryInterface
{
    /**
     * Create an Eloquent collection from an OpenSearch response.
     */
    public function makeFromSearchResponse(SearchResponse $searchResponse, Builder $builder): EloquentCollection
    {
        $documentIds = $this->pluckDocumentIds($searchResponse);

        if (empty($documentIds)) {
            return $builder->model->newCollection();
        }

        /** @var EloquentCollection $models */
        $models = $builder->model->getScoutModelsByIds($builder, $documentIds);

        return $this->sortModels($this->filterModels($models, $documentIds), $documentIds);
    }

    /**
     * Lazily create an Eloquent collection from an OpenSearch response.
     */
    public function makeLazyFromSearchResponse(SearchResponse $searchResponse, Builder $builder): LazyCollection
    {
        $documentIds = $this->pluckDocumentIds($searchResponse);

        if (empty($documentIds)) {
            return LazyCollection::make($builder->model->newCollection());
        }

        /** @var LazyCollection $models */
        $models = $builder->model->queryScoutModelsByIds($builder, $documentIds)->cursor();

        return $this->sortModels($this->filterModels($models, $documentIds), $documentIds);
    }

    /**
     * Get document IDs from the OpenSearch hits.
     *
     * @return array<int, string>
     */
    protected function pluckDocumentIds(SearchResponse $searchResponse): array
    {
        return array_map(
            fn (Hit $hit) => $hit->document()->id(),
            $searchResponse->hits()
        );
    }

    /**
     * Sort models into the same order as the OpenSearch response.
     *
     * @param  array<int, string>  $documentIds
     */
    protected function sortModels(EloquentCollection|LazyCollection $models, array $documentIds): SupportCollection|EloquentCollection|LazyCollection
    {
        $documentIdPositions = array_flip($documentIds);

        return $models->sortBy(
            fn (Model $model) => $documentIdPositions[(string) $model->getScoutKey()]
        )->values();
    }

    /**
     * Remove models that are no longer present in the database.
     *
     * @param  array<int, string>  $documentIds
     */
    protected function filterModels(EloquentCollection|LazyCollection $models, array $documentIds): SupportCollection|EloquentCollection|LazyCollection
    {
        return $models->filter(
            fn (Model $model) => in_array((string) $model->getScoutKey(), $documentIds, true)
        )->values();
    }
}
