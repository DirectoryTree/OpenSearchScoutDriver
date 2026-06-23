<?php

namespace DirectoryTree\OpenSearchScoutDriver\Factories;

use DirectoryTree\OpenSearchAdapter\Search\SearchRequest as OpenSearchRequest;
use DirectoryTree\OpenSearchScoutDriver\SearchRequest;
use Illuminate\Contracts\Support\Arrayable;
use Laravel\Scout\Builder;
use stdClass;

/**
 * Creates OpenSearch search requests from Scout builders.
 */
class SearchRequestFactory implements SearchRequestFactoryInterface
{
    /**
     * Create an OpenSearch request from the given Scout builder.
     *
     * @param  array{page?: int, perPage?: int}  $options
     */
    public function makeFromBuilder(Builder $builder, array $options = []): SearchRequest
    {
        if ($compiled = $this->compileBuilder($builder, $options)) {
            return $this->makeFromCompiled($builder, $compiled);
        }

        $request = new OpenSearchRequest($this->makeQuery($builder));

        if ($sort = $this->makeSort($builder)) {
            $request->sort($sort);
        }

        if (! is_null($from = $this->makeFrom($options))) {
            $request->from($from);
        }

        if (! is_null($size = $this->makeSize($builder, $options))) {
            $request->size($size);
        }

        $this->applyOptions($request, $builder->options);

        return new SearchRequest($this->makeIndex($builder), $request);
    }

    /**
     * Make an OpenSearch request from a compiled builder payload.
     *
     * @param  array{query?: array<string, mixed>|null, sort?: array<int|string, mixed>|null, from?: int|null, size?: int|string|null, aggs?: array<string, mixed>|null, aggregations?: array<string, mixed>|null}  $compiled
     */
    protected function makeFromCompiled(Builder $builder, array $compiled): SearchRequest
    {
        $request = new OpenSearchRequest($compiled['query'] ?? []);

        if (! empty($compiled['sort'])) {
            $request->sort($compiled['sort']);
        }

        if (! empty($compiled['from'])) {
            $request->from((int) $compiled['from']);
        }

        if (isset($compiled['size']) && is_numeric($compiled['size'])) {
            $request->size((int) $compiled['size']);
        }

        if (! empty($compiled['aggs'])) {
            $request->aggregations($compiled['aggs']);
        }

        if (! empty($compiled['aggregations'])) {
            $request->aggregations($compiled['aggregations']);
        }

        $this->applyOptions($request, $builder->options);

        return new SearchRequest($this->makeIndex($builder), $request);
    }

    /**
     * Compile builders that expose their own OpenSearch payload.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>|null
     */
    protected function compileBuilder(Builder $builder, array $options): ?array
    {
        if (! $builder instanceof Arrayable) {
            return null;
        }

        return $builder->toArray($options);
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
        $size = $options['perPage'] ?? $builder->limit;

        return is_numeric($size) ? (int) $size : null;
    }

    /**
     * Apply Scout builder options to the OpenSearch search request.
     *
     * @param  array<string, mixed>  $options
     */
    protected function applyOptions(OpenSearchRequest $request, array $options): void
    {
        if (isset($options['highlight'])) {
            $request->highlight($options['highlight']);
        }

        if (isset($options['rescore'])) {
            $request->rescore($options['rescore']);
        }

        if (isset($options['suggest'])) {
            $request->suggest($options['suggest']);
        }

        if (isset($options['collapse'])) {
            $request->collapse($options['collapse']);
        }

        if (isset($options['aggregations'])) {
            $request->aggregations($options['aggregations']);
        }

        if (isset($options['post_filter'])) {
            $request->postFilter($options['post_filter']);
        }

        if (isset($options['indices_boost'])) {
            $request->indicesBoost($options['indices_boost']);
        }

        if (isset($options['min_score'])) {
            $request->minScore($options['min_score']);
        }

        if (isset($options['script_fields'])) {
            $request->scriptFields($options['script_fields']);
        }

        if (isset($options['search_type'])) {
            $request->searchType($options['search_type']);
        }

        if (isset($options['preference'])) {
            $request->preference($options['preference']);
        }

        if (array_key_exists('_source', $options)) {
            $request->source($options['_source']);
        }

        if (array_key_exists('track_total_hits', $options)) {
            $request->trackTotalHits($options['track_total_hits']);
        }

        if (array_key_exists('track_scores', $options)) {
            $request->trackScores($options['track_scores']);
        }
    }
}
