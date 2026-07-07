<?php

use DirectoryTree\OpenSearchAdapter\Indices\IndexBlueprint;
use DirectoryTree\OpenSearchAdapter\Search\Hit;
use DirectoryTree\OpenSearchAdapter\Search\SearchRequest as OpenSearchRequest;
use DirectoryTree\OpenSearchAdapter\Search\SearchResponse;
use DirectoryTree\OpenSearchAdapter\Testing\Fakes\FakeDocumentManager;
use DirectoryTree\OpenSearchAdapter\Testing\Fakes\FakeIndexManager;
use DirectoryTree\OpenSearchScoutDriver\CursorPaginator;
use DirectoryTree\OpenSearchScoutDriver\Engine;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactory;
use DirectoryTree\OpenSearchScoutDriver\Tests\Fixtures\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;

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

it('cursor paginates models using search after sort values', function () {
    $response = SearchResponse::fake([
        Hit::fake(['name' => 'John'], index: 'clients', id: '1', attributes: ['sort' => ['john@example.com', '1']]),
        Hit::fake(['name' => 'Jane'], index: 'clients', id: '2', attributes: ['sort' => ['jane@example.com', '2']]),
        Hit::fake(['name' => 'Taylor'], index: 'clients', id: '3', attributes: ['sort' => ['taylor@example.com', '3']]),
    ], 'clients');

    $documentManager = new FakeDocumentManager($response);

    $modelFactory = new class implements ModelFactoryInterface
    {
        public function makeFromSearchResponse(SearchResponse $searchResponse, Builder $builder): Collection
        {
            return $builder->model->newCollection([
                new Client(['id' => 1, 'name' => 'John']),
                new Client(['id' => 2, 'name' => 'Jane']),
                new Client(['id' => 3, 'name' => 'Taylor']),
            ]);
        }

        public function makeLazyFromSearchResponse(SearchResponse $searchResponse, Builder $builder): LazyCollection
        {
            return LazyCollection::make($this->makeFromSearchResponse($searchResponse, $builder));
        }
    };

    $engine = new Engine(
        $modelFactory,
        new FakeIndexManager,
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        true,
    );

    $builder = Client::search('')->orderBy('email')->orderBy('id');

    $paginator = $engine->cursorPaginate($builder, 2);

    $request = new OpenSearchRequest([
        'bool' => [
            'must' => [
                'match_all' => new stdClass,
            ],
        ],
    ]);

    $request->sort([
        ['email' => 'asc'],
        ['id' => 'asc'],
    ])->size(3);

    expect($paginator)->toBeInstanceOf(CursorPaginator::class)
        ->and($paginator->items())->toHaveCount(2)
        ->and($paginator->nextCursor()?->parameter(CursorPaginator::SEARCH_AFTER_PARAMETER))->toBe(['jane@example.com', '2'])
        ->and($paginator->previousCursor())->toBeNull();

    $documentManager->assertSearched('clients', $request);
});

it('cursor paginates previous pages by reversing sort directions', function () {
    $response = SearchResponse::fake([
        Hit::fake(['name' => 'John'], index: 'clients', id: '1', attributes: ['sort' => ['john@example.com', '1']]),
        Hit::fake(['name' => 'Jane'], index: 'clients', id: '2', attributes: ['sort' => ['jane@example.com', '2']]),
        Hit::fake(['name' => 'Taylor'], index: 'clients', id: '3', attributes: ['sort' => ['taylor@example.com', '3']]),
    ], 'clients');

    $documentManager = new FakeDocumentManager($response);

    $modelFactory = new class implements ModelFactoryInterface
    {
        public function makeFromSearchResponse(SearchResponse $searchResponse, Builder $builder): Collection
        {
            return $builder->model->newCollection([
                new Client(['id' => 1, 'name' => 'John']),
                new Client(['id' => 2, 'name' => 'Jane']),
                new Client(['id' => 3, 'name' => 'Taylor']),
            ]);
        }

        public function makeLazyFromSearchResponse(SearchResponse $searchResponse, Builder $builder): LazyCollection
        {
            return LazyCollection::make($this->makeFromSearchResponse($searchResponse, $builder));
        }
    };

    $engine = new Engine(
        $modelFactory,
        new FakeIndexManager,
        $documentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        true,
    );

    $cursor = new Cursor([
        CursorPaginator::SEARCH_AFTER_PARAMETER => ['jane@example.com', '2'],
    ], false);

    $engine->cursorPaginate(Client::search('')->orderBy('email')->orderBy('id'), 2, cursor: $cursor);

    $request = new OpenSearchRequest([
        'bool' => [
            'must' => [
                'match_all' => new stdClass,
            ],
        ],
    ]);

    $request->sort([
        ['email' => 'desc'],
        ['id' => 'desc'],
    ])->size(3)->searchAfter(['jane@example.com', '2']);

    $documentManager->assertSearched('clients', $request);
});

it('requires an explicit sort for cursor pagination', function () {
    $engine = new Engine(
        new ModelFactory,
        new FakeIndexManager,
        new FakeDocumentManager,
        new DocumentFactory,
        new SearchRequestFactory,
        true,
    );

    $engine->cursorPaginate(Client::search(''), 2);
})->throws(InvalidArgumentException::class, 'OpenSearch cursor pagination requires at least one explicit sort.');

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
