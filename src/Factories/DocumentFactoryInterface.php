<?php

namespace DirectoryTree\OpenSearchScoutDriver\Factories;

use Illuminate\Support\Collection;

interface DocumentFactoryInterface
{
    /**
     * Create OpenSearch documents from the given model collection.
     */
    public function makeFromModels(Collection $models): Collection;
}
