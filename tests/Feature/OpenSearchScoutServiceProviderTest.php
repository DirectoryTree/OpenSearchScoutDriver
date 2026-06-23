<?php

use DirectoryTree\OpenSearchAdapter\Documents\DocumentManagerInterface;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManagerInterface;
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
        ->and(app(DocumentManagerInterface::class))->toBeInstanceOf(DocumentManagerInterface::class)
        ->and(app(IndexManagerInterface::class))->toBeInstanceOf(IndexManagerInterface::class);
});
