<?php

namespace DirectoryTree\OpenSearchScoutDriver\Tests;

use DirectoryTree\OpenSearchClient\OpenSearchClientServiceProvider;
use DirectoryTree\OpenSearchScoutDriver\OpenSearchScoutServiceProvider;
use Laravel\Scout\ScoutServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            ScoutServiceProvider::class,
            OpenSearchClientServiceProvider::class,
            OpenSearchScoutServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('scout.driver', 'opensearch');
        $app['config']->set('opensearch-scout.refresh_documents', true);
        $app['config']->set('opensearch-client.default', 'default');
        $app['config']->set('opensearch-client.connections.default', [
            'base_uri' => env('OPENSEARCH_HOST', 'http://127.0.0.1:9200'),
        ]);
    }
}
