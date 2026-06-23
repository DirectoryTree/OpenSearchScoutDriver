<?php

use DirectoryTree\OpenSearchAdapter\Search\SearchResponse;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactory;
use DirectoryTree\OpenSearchScoutDriver\Tests\Fixtures\Client;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Builder;

it('returns an empty collection for empty search responses', function () {
    $builder = new Builder(new Client, 'john');

    $response = new SearchResponse([
        'hits' => [
            'total' => ['value' => 0],
            'hits' => [],
        ],
    ]);

    expect((new ModelFactory)->makeFromSearchResponse($response, $builder))->toBeInstanceOf(Collection::class)->toBeEmpty();
});
