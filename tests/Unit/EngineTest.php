<?php

use DirectoryTree\OpenSearchAdapter\Documents\DocumentManager;
use DirectoryTree\OpenSearchAdapter\Indices\IndexBlueprint;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManager;
use DirectoryTree\OpenSearchAdapter\Search\SearchResponse;
use DirectoryTree\OpenSearchScoutDriver\Engine;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactory;
use DirectoryTree\OpenSearchScoutDriver\Tests\Fixtures\Client;
use Illuminate\Database\Eloquent\Collection;

it('does not index empty model collections', function () {
    $documentManager = Mockery::mock(DocumentManager::class);
    $documentManager->shouldNotReceive('index');

    $engine = new Engine(
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        new ModelFactory,
        Mockery::mock(IndexManager::class),
    );

    $engine->update((new Client)->newCollection());
});

it('indexes model collections', function () {
    $models = new Collection([
        new Client(['id' => 1, 'name' => 'John']),
        new Client(['id' => 2, 'name' => 'Jane']),
    ]);

    $documentManager = Mockery::mock(DocumentManager::class);
    $documentManager
        ->shouldReceive('index')
        ->once()
        ->with('clients', Mockery::on(fn (array $documents) => count($documents) === 2), true);

    $engine = new Engine(
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        new ModelFactory,
        Mockery::mock(IndexManager::class),
    );

    $engine->update($models);
});

it('does not delete empty model collections', function () {
    $documentManager = Mockery::mock(DocumentManager::class);
    $documentManager->shouldNotReceive('delete');

    $engine = new Engine(
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        new ModelFactory,
        Mockery::mock(IndexManager::class),
    );

    $engine->delete((new Client)->newCollection());
});

it('deletes model collections', function () {
    $models = new Collection([
        new Client(['id' => 1, 'name' => 'John']),
        new Client(['id' => 2, 'name' => 'Jane']),
    ]);

    $documentManager = Mockery::mock(DocumentManager::class);
    $documentManager
        ->shouldReceive('delete')
        ->once()
        ->with('clients', ['1', '2'], true);

    $engine = new Engine(
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        new ModelFactory,
        Mockery::mock(IndexManager::class),
    );

    $engine->delete($models);
});

it('searches using the generated index and request', function () {
    $response = new SearchResponse([
        'hits' => [
            'total' => ['value' => 0],
            'hits' => [],
        ],
    ]);

    $documentManager = Mockery::mock(DocumentManager::class);
    $documentManager
        ->shouldReceive('search')
        ->once()
        ->with('clients', Mockery::any())
        ->andReturn($response);

    $engine = new Engine(
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        new ModelFactory,
        Mockery::mock(IndexManager::class),
    );

    expect($engine->search(Client::search('john')))->toBe($response);
});

it('flushes model indexes', function () {
    $documentManager = Mockery::mock(DocumentManager::class);
    $documentManager
        ->shouldReceive('deleteByQuery')
        ->once()
        ->with('clients', Mockery::on(fn (array $query) => array_key_exists('match_all', $query)), true);

    $engine = new Engine(
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        new ModelFactory,
        Mockery::mock(IndexManager::class),
    );

    $engine->flush(new Client);
});

it('creates and deletes indexes', function () {
    $indexManager = Mockery::mock(IndexManager::class);
    $indexManager->shouldReceive('create')->once()->with(Mockery::type(IndexBlueprint::class));
    $indexManager->shouldReceive('drop')->once()->with('clients');

    $engine = new Engine(
        Mockery::mock(DocumentManager::class),
        new DocumentFactory,
        new SearchRequestFactory,
        new ModelFactory,
        $indexManager,
    );

    $engine->createIndex('clients');
    $engine->deleteIndex('clients');
});

it('rejects custom primary keys when creating indexes', function () {
    $engine = new Engine(
        Mockery::mock(DocumentManager::class),
        new DocumentFactory,
        new SearchRequestFactory,
        new ModelFactory,
        Mockery::mock(IndexManager::class),
    );

    $engine->createIndex('clients', ['primaryKey' => 'uuid']);
})->throws(InvalidArgumentException::class);
