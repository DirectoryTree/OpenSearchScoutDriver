<?php

use DirectoryTree\OpenSearchAdapter\Search\Hit;
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

it('hydrates hits when total hit metadata is not returned', function () {
    $model = new class extends Client
    {
        public function getScoutModelsByIds($builder, array $ids)
        {
            return $this->newCollection(array_map(
                fn (string $id) => new Client(['id' => (int) $id]),
                $ids,
            ));
        }
    };

    $builder = new Builder($model, 'john');

    $response = new SearchResponse([
        'hits' => [
            'hits' => [
                Hit::fake(['name' => 'John'], id: '1')->raw(),
            ],
        ],
    ]);

    expect((new ModelFactory)->makeFromSearchResponse($response, $builder))
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1);
});
