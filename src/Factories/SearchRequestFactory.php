<?php

namespace DirectoryTree\OpenSearchScoutDriver\Factories;

use DirectoryTree\OpenSearchAdapter\Search\SearchRequest as OpenSearchRequest;
use DirectoryTree\OpenSearchScoutDriver\SearchRequest;
use Laravel\Scout\Builder;
use stdClass;

class SearchRequestFactory implements SearchRequestFactoryInterface
{
    /**
     * Create an OpenSearch request from the given Scout builder.
     *
     * @param  array{page?: int, perPage?: int}  $options
     */
    public function makeFromBuilder(Builder $builder, array $options = []): SearchRequest
    {
        $request = new OpenSearchRequest($this->makeQuery($builder));

        if ($sort = $this->makeSort($builder)) {
            $request->sort($sort);
        }

        if ($from = $this->makeFrom($options)) {
            $request->from($from);
        }

        if ($size = $this->makeSize($builder, $options)) {
            $request->size($size);
        }

        return new SearchRequest($this->makeIndex($builder), $request);
    }

    /**
     * Get the OpenSearch index name for the builder.
     */
    protected function makeIndex(Builder $builder): string
    {
        return $builder->index ?: $builder->model->searchableAs();
    }

    /**
     * Create the OpenSearch query body.
     *
     * @return array<string, mixed>
     */
    protected function makeQuery(Builder $builder): array
    {
        $query = [
            'bool' => [],
        ];

        if (! empty($builder->query)) {
            $query['bool']['must'] = [
                'query_string' => [
                    'query' => $builder->query,
                ],
            ];
        } else {
            $query['bool']['must'] = [
                'match_all' => new stdClass,
            ];
        }

        if ($filter = $this->makeFilter($builder)) {
            $query['bool']['filter'] = $filter;
        }

        return $query;
    }

    /**
     * Create the OpenSearch filter clauses.
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function makeFilter(Builder $builder): ?array
    {
        $filter = [];

        foreach ($builder->wheres as $field => $value) {
            if (is_array($value) && isset($value['field'], $value['value'])) {
                $filter[] = ['term' => [$value['field'] => $value['value']]];

                continue;
            }

            $filter[] = ['term' => [$field => $value]];
        }

        if (property_exists($builder, 'whereIns')) {
            foreach ($builder->whereIns as $field => $values) {
                $filter[] = ['terms' => [$field => $values]];
            }
        }

        return empty($filter) ? null : $filter;
    }

    /**
     * Create the OpenSearch sort clauses.
     *
     * @return array<int, array<string, string>>|null
     */
    protected function makeSort(Builder $builder): ?array
    {
        $sort = [];

        foreach ($builder->orders as $order) {
            $sort[] = [$order['column'] => $order['direction']];
        }

        return empty($sort) ? null : $sort;
    }

    /**
     * Create the OpenSearch result offset.
     *
     * @param  array{page?: int, perPage?: int}  $options
     */
    protected function makeFrom(array $options): ?int
    {
        if (isset($options['page'], $options['perPage'])) {
            return ($options['page'] - 1) * $options['perPage'];
        }

        return null;
    }

    /**
     * Create the OpenSearch result size.
     *
     * @param  array{perPage?: int}  $options
     */
    protected function makeSize(Builder $builder, array $options): ?int
    {
        return $options['perPage'] ?? $builder->limit;
    }
}
