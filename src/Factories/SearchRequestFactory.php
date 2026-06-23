<?php

namespace DirectoryTree\OpenSearchScoutDriver\Factories;

use DirectoryTree\OpenSearchAdapter\Search\SearchRequest as OpenSearchRequest;
use DirectoryTree\OpenSearchScoutDriver\SearchRequest;
use DirectoryTree\OpenSearchScoutDriver\SearchRequestPayload;
use DirectoryTree\OpenSearchScoutDriver\SearchRequestPayloadInterface;
use Laravel\Scout\Builder;

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
        $payload = $builder instanceof SearchRequestPayloadInterface
            ? $builder->toSearchRequestPayload($options)
            : SearchRequestPayload::fromBuilder($builder, $options);

        return $this->makeFromPayload($builder, $payload);
    }

    /**
     * Make an OpenSearch request from a builder payload.
     */
    protected function makeFromPayload(Builder $builder, SearchRequestPayload $payload): SearchRequest
    {
        $request = new OpenSearchRequest($payload->query());

        if ($sort = $payload->sort()) {
            $request->sort($sort);
        }

        if (! is_null($from = $payload->from())) {
            $request->from($from);
        }

        if (! is_null($size = $payload->size())) {
            $request->size($size);
        }

        if ($aggregations = $payload->aggregations()) {
            $request->aggregations($aggregations);
        }

        $this->applyOptions($request, $builder->options);

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

        if (array_key_exists('track_scores', $options)) {
            $request->trackScores($options['track_scores']);
        }

        if (array_key_exists('track_total_hits', $options)) {
            $request->trackTotalHits($options['track_total_hits']);
        }
    }
}
