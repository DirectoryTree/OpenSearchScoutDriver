<?php

namespace DirectoryTree\OpenSearchScoutDriver\Factories;

use DirectoryTree\OpenSearchAdapter\Search\SearchResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;

/**
 * Defines a factory for creating Scout model results from OpenSearch responses.
 */
interface ModelFactoryInterface
{
    /**
     * Create an Eloquent collection from an OpenSearch response.
     */
    public function makeFromSearchResponse(SearchResponse $searchResponse, Builder $builder): Collection;

    /**
     * Lazily create an Eloquent collection from an OpenSearch response.
     */
    public function makeLazyFromSearchResponse(SearchResponse $searchResponse, Builder $builder): LazyCollection;
}
