<?php

use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactory;
use DirectoryTree\OpenSearchScoutDriver\Tests\Fixtures\Client;
use Illuminate\Contracts\Support\Arrayable;
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

it('creates search requests with zero limits', function () {
    $builder = new Builder(new Client, 'john');
    $builder->take(0);

    $request = (new SearchRequestFactory)->makeFromBuilder($builder);

    expect($request->toArray()['body']['size'])->toBe(0);
});

it('creates search requests with pagination', function () {
    $request = (new SearchRequestFactory)->makeFromBuilder(new Builder(new Client, 'john'), [
        'page' => 3,
        'perPage' => 30,
    ]);

    expect($request->toArray()['body']['from'])->toBe(60)
        ->and($request->toArray()['body']['size'])->toBe(30);
});

it('creates search requests with opensearch body options', function () {
    $builder = (new Builder(new Client, 'john'))->options([
        'highlight' => ['fields' => ['name' => new stdClass]],
        'rescore' => ['window_size' => 50],
        'suggest' => ['name_suggest' => ['text' => 'john']],
        '_source' => false,
        'collapse' => ['field' => 'email'],
        'aggregations' => ['emails' => ['terms' => ['field' => 'email']]],
        'post_filter' => ['term' => ['active' => true]],
        'track_total_hits' => true,
        'indices_boost' => [['clients' => 1.5]],
        'track_scores' => false,
        'min_score' => 1.25,
        'script_fields' => ['score_name' => ['script' => ['source' => 'doc["name"].value']]],
    ]);

    $request = (new SearchRequestFactory)->makeFromBuilder($builder)->toArray();

    expect($request['body']['highlight'])->toEqual(['fields' => ['name' => new stdClass]])
        ->and($request['body']['rescore'])->toBe(['window_size' => 50])
        ->and($request['body']['suggest'])->toBe(['name_suggest' => ['text' => 'john']])
        ->and($request['body']['_source'])->toBeFalse()
        ->and($request['body']['collapse'])->toBe(['field' => 'email'])
        ->and($request['body']['aggregations'])->toBe(['emails' => ['terms' => ['field' => 'email']]])
        ->and($request['body']['post_filter'])->toBe(['term' => ['active' => true]])
        ->and($request['body']['track_total_hits'])->toBeTrue()
        ->and($request['body']['indices_boost'])->toBe([['clients' => 1.5]])
        ->and($request['body']['track_scores'])->toBeFalse()
        ->and($request['body']['min_score'])->toBe(1.25)
        ->and($request['body']['script_fields'])->toBe(['score_name' => ['script' => ['source' => 'doc["name"].value']]]);
});

it('creates search requests with opensearch top-level options', function () {
    $builder = (new Builder(new Client, 'john'))->options([
        'search_type' => 'dfs_query_then_fetch',
        'preference' => '_local',
    ]);

    $request = (new SearchRequestFactory)->makeFromBuilder($builder)->toArray();

    expect($request['search_type'])->toBe('dfs_query_then_fetch')
        ->and($request['preference'])->toBe('_local');
});

it('creates search requests from compiled arrayable builders', function () {
    $builder = new class(new Client, null) extends Builder implements Arrayable
    {
        /**
         * Compile the query into its array form.
         *
         * @param  array<string, mixed>  $options
         * @return array<string, mixed>
         */
        public function toArray(array $options = []): array
        {
            return [
                'query' => [],
                'sort' => [
                    ['foo' => ['order' => 'desc']],
                ],
                'from' => ($options['page'] - 1) * $options['perPage'],
                'size' => $options['perPage'],
                'aggs' => [
                    'emails' => ['terms' => ['field' => 'email']],
                ],
            ];
        }
    };

    $builder->options([
        'track_total_hits' => 5_000_000,
    ]);

    $request = (new SearchRequestFactory)->makeFromBuilder($builder, [
        'page' => 2,
        'perPage' => 0,
    ])->toArray();

    expect($request)->toEqual([
        'body' => [
            'sort' => [
                ['foo' => ['order' => 'desc']],
            ],
            'size' => 0,
            'aggregations' => [
                'emails' => ['terms' => ['field' => 'email']],
            ],
            'track_total_hits' => 5_000_000,
        ],
        'index' => 'clients',
    ]);
});
