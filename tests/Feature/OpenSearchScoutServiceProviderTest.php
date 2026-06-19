<?php

use DirectoryTree\OpenSearchAdapter\Documents\DocumentManager;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManager;
use DirectoryTree\OpenSearchScoutDriver\Engine;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactoryInterface;
use Laravel\Scout\EngineManager;

it('registers the opensearch scout engine', function () {
    expect(app(EngineManager::class)->engine('opensearch'))->toBeInstanceOf(Engine::class);
});

it('registers package bindings', function () {
    expect(app(DocumentFactoryInterface::class))->toBeInstanceOf(DocumentFactoryInterface::class)
        ->and(app(ModelFactoryInterface::class))->toBeInstanceOf(ModelFactoryInterface::class)
        ->and(app(SearchRequestFactoryInterface::class))->toBeInstanceOf(SearchRequestFactoryInterface::class)
        ->and(app(DocumentManager::class))->toBeInstanceOf(DocumentManager::class)
        ->and(app(IndexManager::class))->toBeInstanceOf(IndexManager::class);
});
