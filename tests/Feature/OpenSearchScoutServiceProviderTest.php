<?php

use DirectoryTree\OpenSearchAdapter\Documents\DocumentManagerInterface;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManagerInterface;
use DirectoryTree\OpenSearchAdapter\Testing\Fakes\FakeDocumentManager;
use DirectoryTree\OpenSearchAdapter\Testing\Fakes\FakeIndexManager;
use DirectoryTree\OpenSearchScoutDriver\CursorPaginator;
use DirectoryTree\OpenSearchScoutDriver\Engine;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Tests\Fixtures\Client;
use Laravel\Scout\Builder;
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

it('registers the cursor paginate builder macro', function () {
    expect(Builder::hasMacro('cursorPaginate'))->toBeTrue();
});

it('appends the search query to cursor pagination urls', function () {
    app()->bind(Engine::class, fn () => new class extends Engine
    {
        public function __construct()
        {
            parent::__construct(
                new ModelFactory,
                new FakeIndexManager,
                new FakeDocumentManager,
                new DocumentFactory,
                new SearchRequestFactory,
                true,
            );
        }

        public function cursorPaginate(Builder $builder, $perPage = null, $cursorName = 'cursor', $cursor = null): CursorPaginator
        {
            return new CursorPaginator(
                (new Client)->newCollection([
                    new Client(['id' => 1]),
                    new Client(['id' => 2]),
                ]),
                1,
                null,
                [
                    'cursorName' => $cursorName,
                    'path' => '/clients',
                    'parameters' => [CursorPaginator::SEARCH_AFTER_PARAMETER],
                    'searchAfter' => [
                        '1' => ['john@example.com', '1'],
                        '2' => ['jane@example.com', '2'],
                    ],
                ],
            );
        }
    });

    app(EngineManager::class)->forgetDrivers();

    $paginator = Client::search('john')->cursorPaginate(1);

    expect($paginator->nextPageUrl())->toContain('query=john');
});
