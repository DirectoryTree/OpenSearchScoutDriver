<?php

namespace DirectoryTree\OpenSearchScoutDriver\Factories;

use DirectoryTree\OpenSearchScoutDriver\SearchRequest;
use Laravel\Scout\Builder;

interface SearchRequestFactoryInterface
{
    /**
     * Create an OpenSearch request from the given Scout builder.
     *
     * @param  array{page?: int, perPage?: int}  $options
     */
    public function makeFromBuilder(Builder $builder, array $options = []): SearchRequest;
}
