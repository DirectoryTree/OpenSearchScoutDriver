<?php

namespace DirectoryTree\OpenSearchScoutDriver\Factories;

use DirectoryTree\OpenSearchAdapter\Search\Hit;
use DirectoryTree\OpenSearchAdapter\Search\SearchResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;

class ModelFactory implements ModelFactoryInterface
{
    /**
     * Create an Eloquent collection from an OpenSearch response.
     */
    public function makeFromSearchResponse(SearchResponse $searchResponse, Builder $builder): Collection
    {
        if (! $searchResponse->total()) {
            return $builder->model->newCollection();
        }

        $documentIds = $this->pluckDocumentIds($searchResponse);

        /** @var Collection $models */
        $models = $builder->model->getScoutModelsByIds($builder, $documentIds);

        return $this->sortModels($this->filterModels($models, $documentIds), $documentIds);
    }

    /**
     * Lazily create an Eloquent collection from an OpenSearch response.
     */
    public function makeLazyFromSearchResponse(SearchResponse $searchResponse, Builder $builder): LazyCollection
    {
        if (! $searchResponse->total()) {
            return LazyCollection::make($builder->model->newCollection());
        }

        $documentIds = $this->pluckDocumentIds($searchResponse);

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
            static fn (Hit $hit) => $hit->document()->id(),
            $searchResponse->hits()
        );
    }

    /**
     * Remove models that are no longer present in the database.
     *
     * @template T
     *
     * @param  T  $models
     * @param  array<int, string>  $documentIds
     * @return T
     */
    protected function filterModels($models, array $documentIds)
    {
        return $models->filter(static fn (Model $model) => in_array((string) $model->getScoutKey(), $documentIds, true))->values();
    }

    /**
     * Sort models into the same order as the OpenSearch response.
     *
     * @template T
     *
     * @param  T  $models
     * @param  array<int, string>  $documentIds
     * @return T
     */
    protected function sortModels($models, array $documentIds)
    {
        $documentIdPositions = array_flip($documentIds);

        return $models->sortBy(static fn (Model $model) => $documentIdPositions[(string) $model->getScoutKey()])->values();
    }
}
