<?php

use DirectoryTree\OpenSearchScoutDriver\SearchRequestPayload;
use DirectoryTree\OpenSearchScoutDriver\Tests\Fixtures\Client;
use Laravel\Scout\Builder;

it('can be created from scout builders', function () {
    $builder = (new Builder(new Client, 'john'))
        ->where('email', 'john@example.com')
        ->whereIn('status', ['active', 'invited']);

    $builder->orderBy('email');

    $payload = SearchRequestPayload::fromBuilder($builder, [
        'page' => 2,
        'perPage' => 15,
    ]);

    expect($payload->toArray())->toEqual([
        'query' => [
            'bool' => [
                'must' => [
                    'query_string' => ['query' => 'john'],
                ],
                'filter' => [
                    ['term' => ['email' => 'john@example.com']],
                    ['terms' => ['status' => ['active', 'invited']]],
                ],
            ],
        ],
        'sort' => [
            ['email' => 'asc'],
        ],
        'from' => 15,
        'size' => 15,
    ]);
});

it('can be created with search after values', function () {
    $builder = (new Builder(new Client, 'john'))
        ->orderBy('email')
        ->orderBy('id');

    $payload = SearchRequestPayload::fromBuilder($builder, [
        'perPage' => 15,
        'searchAfter' => ['john@example.com', 1],
    ]);

    expect($payload->toArray())->toEqual([
        'query' => [
            'bool' => [
                'must' => [
                    'query_string' => ['query' => 'john'],
                ],
            ],
        ],
        'sort' => [
            ['email' => 'asc'],
            ['id' => 'asc'],
        ],
        'size' => 15,
        'search_after' => ['john@example.com', 1],
    ]);
});

it('filters empty payload values when cast to an array', function () {
    $payload = new SearchRequestPayload(
        aggregations: [
            'emails' => ['terms' => ['field' => 'email']],
        ],
    );

    expect($payload->toArray())->toEqual([
        'aggregations' => [
            'emails' => ['terms' => ['field' => 'email']],
        ],
    ]);
});
