<?php

namespace DirectoryTree\OpenSearchScoutDriver\Factories;

use DirectoryTree\OpenSearchScoutDriver\SearchRequest;
use Laravel\Scout\Builder;

/**
 * Defines a factory for creating OpenSearch search requests from Scout builders.
 */
interface SearchRequestFactoryInterface
{
    /**
     * Create an OpenSearch request from the given Scout builder.
     *
     * @param  array{page?: int, perPage?: int}  $options
     */
    public function makeFromBuilder(Builder $builder, array $options = []): SearchRequest;
}
