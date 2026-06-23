<?php

namespace DirectoryTree\OpenSearchScoutDriver;

/**
 * Defines a builder that provides a complete OpenSearch search request payload.
 */
interface SearchRequestPayloadInterface
{
    /**
     * Get the OpenSearch search request payload.
     *
     * @param  array<string, mixed>  $options
     */
    public function toSearchRequestPayload(array $options = []): SearchRequestPayload;
}
