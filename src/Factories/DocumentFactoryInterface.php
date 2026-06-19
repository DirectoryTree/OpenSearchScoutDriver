<?php

namespace DirectoryTree\OpenSearchScoutDriver\Factories;

use Illuminate\Support\Collection;

/**
 * Defines a factory for creating OpenSearch documents from Scout models.
 */
interface DocumentFactoryInterface
{
    /**
     * Create OpenSearch documents from the given model collection.
     */
    public function makeFromModels(Collection $models): Collection;
}
