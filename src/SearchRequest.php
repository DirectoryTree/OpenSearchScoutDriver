<?php

namespace DirectoryTree\OpenSearchScoutDriver;

use DirectoryTree\OpenSearchAdapter\Search\SearchRequest as OpenSearchRequest;

class SearchRequest
{
    /**
     * Create a new Scout search request instance.
     */
    public function __construct(
        protected string $indexName,
        protected OpenSearchRequest $request,
    ) {}

    /**
     * Get the OpenSearch index name.
     */
    public function indexName(): string
    {
        return $this->indexName;
    }

    /**
     * Get the OpenSearch request.
     */
    public function request(): OpenSearchRequest
    {
        return $this->request;
    }

    /**
     * Get the OpenSearch search parameters.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge($this->request->toArray(), [
            'index' => $this->indexName,
        ]);
    }
}
