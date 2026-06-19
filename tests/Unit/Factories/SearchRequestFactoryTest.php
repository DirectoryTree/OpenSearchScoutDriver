<?php

use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactory;
use DirectoryTree\OpenSearchScoutDriver\Tests\Fixtures\Client;
use Laravel\Scout\Builder;

it('creates search requests with empty query strings', function () {
    $model = new Client;
    $request = (new SearchRequestFactory)->makeFromBuilder(new Builder($model, ''));

    expect($request->toArray())->toEqual([
        'body' => [
            'query' => [
                'bool' => [
                    'must' => [
                        'match_all' => new stdClass,
                    ],
                ],
            ],
        ],
        'index' => 'clients',
    ]);
});

it('creates search requests with query strings', function () {
    $request = (new SearchRequestFactory)->makeFromBuilder(new Builder(new Client, 'john'));

    expect($request->toArray())->toBe([
        'body' => [
            'query' => [
                'bool' => [
                    'must' => [
                        'query_string' => ['query' => 'john'],
                    ],
                ],
            ],
        ],
        'index' => 'clients',
    ]);
});

it('creates search requests with filters', function () {
    $builder = (new Builder(new Client, 'john'))->where('email', 'john@example.com');
    $request = (new SearchRequestFactory)->makeFromBuilder($builder);

    expect($request->toArray()['body']['query']['bool']['filter'])->toBe([
        ['term' => ['email' => 'john@example.com']],
    ]);
});

it('creates search requests with where-in filters', function () {
    $builder = (new Builder(new Client, 'john'))->whereIn('email', ['john@example.com', 'jane@example.com']);
    $request = (new SearchRequestFactory)->makeFromBuilder($builder);

    expect($request->toArray()['body']['query']['bool']['filter'])->toBe([
        ['terms' => ['email' => ['john@example.com', 'jane@example.com']]],
    ]);
});

it('creates search requests with sort clauses', function () {
    $builder = new Builder(new Client, 'john');
    $builder->orderBy('email');
    $builder->orderBy('name', 'desc');

    $request = (new SearchRequestFactory)->makeFromBuilder($builder);

    expect($request->toArray()['body']['sort'])->toBe([
        ['email' => 'asc'],
        ['name' => 'desc'],
    ]);
});

it('creates search requests with limits', function () {
    $builder = new Builder(new Client, 'john');
    $builder->take(10);

    $request = (new SearchRequestFactory)->makeFromBuilder($builder);

    expect($request->toArray()['body']['size'])->toBe(10);
});

it('creates search requests with pagination', function () {
    $request = (new SearchRequestFactory)->makeFromBuilder(new Builder(new Client, 'john'), [
        'page' => 3,
        'perPage' => 30,
    ]);

    expect($request->toArray()['body']['from'])->toBe(60)
        ->and($request->toArray()['body']['size'])->toBe(30);
});
