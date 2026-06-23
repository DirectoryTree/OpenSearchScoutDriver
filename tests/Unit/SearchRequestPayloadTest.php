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
