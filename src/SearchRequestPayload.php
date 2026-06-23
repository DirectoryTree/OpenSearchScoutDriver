<?php

namespace DirectoryTree\OpenSearchScoutDriver;

use Laravel\Scout\Builder;
use stdClass;

/**
 * Represents an OpenSearch search request payload.
 */
class SearchRequestPayload
{
    /**
     * Create a new search request payload instance.
     *
     * @param  array<string, mixed>  $query
     * @param  array<int|string, mixed>|null  $sort
     * @param  array<string, mixed>|null  $aggregations
     */
    public function __construct(
        protected array $query = [],
        protected ?int $from = null,
        protected ?int $size = null,
        protected ?array $sort = null,
        protected ?array $aggregations = null,
    ) {}

    /**
     * Create a new search request payload from the given Scout builder.
     *
     * @param  array{page?: int, perPage?: int}  $options
     */
    public static function fromBuilder(Builder $builder, array $options = []): self
    {
        return new self(
            query: static::makeQuery($builder),
            from: static::makeFrom($options),
            size: static::makeSize($builder, $options),
            sort: static::makeSort($builder),
        );
    }

    /**
     * Get the query definition.
     *
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    /**
     * Get the sort definitions.
     *
     * @return array<int|string, mixed>|null
     */
    public function sort(): ?array
    {
        return $this->sort;
    }

    /**
     * Get the result offset.
     */
    public function from(): ?int
    {
        return $this->from;
    }

    /**
     * Get the maximum number of results.
     */
    public function size(): ?int
    {
        return $this->size;
    }

    /**
     * Get the aggregation definitions.
     *
     * @return array<string, mixed>|null
     */
    public function aggregations(): ?array
    {
        return $this->aggregations;
    }

    /**
     * Get the OpenSearch search request payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'query' => $this->query,
            'sort' => $this->sort,
            'from' => $this->from,
            'size' => $this->size,
            'aggregations' => $this->aggregations,
        ], fn (mixed $value) => filled($value));
    }

    /**
     * Create the OpenSearch query body.
     *
     * @return array<string, mixed>
     */
    protected static function makeQuery(Builder $builder): array
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

        if ($filter = static::makeFilter($builder)) {
            $query['bool']['filter'] = $filter;
        }

        return $query;
    }

    /**
     * Create the OpenSearch filter clauses.
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected static function makeFilter(Builder $builder): ?array
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
    protected static function makeSort(Builder $builder): ?array
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
    protected static function makeFrom(array $options): ?int
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
    protected static function makeSize(Builder $builder, array $options): ?int
    {
        $size = $options['perPage'] ?? $builder->limit;

        return is_numeric($size) ? (int) $size : null;
    }
}
