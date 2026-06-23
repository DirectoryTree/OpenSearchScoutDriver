<?php

use DirectoryTree\OpenSearchAdapter\Indices\IndexBlueprint;
use DirectoryTree\OpenSearchAdapter\Search\SearchResponse;
use DirectoryTree\OpenSearchAdapter\Testing\Fakes\FakeDocumentManager;
use DirectoryTree\OpenSearchAdapter\Testing\Fakes\FakeIndexManager;
use DirectoryTree\OpenSearchScoutDriver\Engine;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactory;
use DirectoryTree\OpenSearchScoutDriver\Tests\Fixtures\Client;
use Illuminate\Database\Eloquent\Collection;

it('does not index empty model collections', function () {
    $documentManager = new class extends FakeDocumentManager
    {
        /**
         * Count the indexed document operations.
         */
        public function indexedCount(): int
        {
            return count($this->indexed);
        }
    };

    $engine = new Engine(
        new ModelFactory,
        new FakeIndexManager,
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        true,
    );

    $engine->update((new Client)->newCollection());

    expect($documentManager->indexedCount())->toBe(0);
});

it('indexes model collections', function () {
    $models = new Collection([
        new Client(['id' => 1, 'name' => 'John']),
        new Client(['id' => 2, 'name' => 'Jane']),
    ]);

    $documentManager = new FakeDocumentManager;

    $engine = new Engine(
        new ModelFactory,
        new FakeIndexManager,
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        true,
    );

    $engine->update($models);

    $documentManager->assertIndexed(
        'clients',
        (new DocumentFactory)->makeFromModels($models)->all(),
        true,
    );
});

it('does not delete empty model collections', function () {
    $documentManager = new class extends FakeDocumentManager
    {
        /**
         * Count the deleted document operations.
         */
        public function deletedCount(): int
        {
            return count($this->deleted);
        }
    };

    $engine = new Engine(
        new ModelFactory,
        new FakeIndexManager,
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        true,
    );

    $engine->delete((new Client)->newCollection());

    expect($documentManager->deletedCount())->toBe(0);
});

it('deletes model collections', function () {
    $models = new Collection([
        new Client(['id' => 1, 'name' => 'John']),
        new Client(['id' => 2, 'name' => 'Jane']),
    ]);

    $documentManager = new FakeDocumentManager;

    $engine = new Engine(
        new ModelFactory,
        new FakeIndexManager,
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        true,
    );

    $engine->delete($models);

    $documentManager->assertDeleted('clients', ['1', '2'], true);
});

it('searches using the generated index and request', function () {
    $response = new SearchResponse;
    $documentManager = new FakeDocumentManager($response);

    $engine = new Engine(
        new ModelFactory,
        new FakeIndexManager,
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        true,
    );

    $builder = Client::search('john');
    $request = (new SearchRequestFactory)->makeFromBuilder($builder);

    expect($engine->search($builder))->toBe($response);

    $documentManager->assertSearched('clients', $request->request());
});

it('flushes model indexes', function () {
    $documentManager = new FakeDocumentManager;

    $engine = new Engine(
        new ModelFactory,
        new FakeIndexManager,
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        true,
    );

    $engine->flush(new Client);

    $documentManager->assertDeletedByQuery('clients', ['match_all' => new stdClass], true);
});

it('creates and deletes indexes', function () {
    $indexManager = new FakeIndexManager;

    $engine = new Engine(
        new ModelFactory,
        $indexManager,
        new FakeDocumentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        true,
    );

    $engine->createIndex('clients');
    $engine->deleteIndex('clients');

    $indexManager->assertCreated(new IndexBlueprint('clients'));
    $indexManager->assertDeleted('clients');
});

it('rejects custom primary keys when creating indexes', function () {
    $engine = new Engine(
        new ModelFactory,
        new FakeIndexManager,
        new FakeDocumentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        true,
    );

    $engine->createIndex('clients', ['primaryKey' => 'uuid']);
})->throws(InvalidArgumentException::class);
