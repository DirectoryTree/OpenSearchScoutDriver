<?php

namespace DirectoryTree\OpenSearchScoutDriver;

use DirectoryTree\OpenSearchAdapter\Documents\DocumentManager;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManager;
use DirectoryTree\OpenSearchClient\ClientBuilderInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactoryInterface;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;

/**
 * Registers the OpenSearch Scout driver package.
 */
class OpenSearchScoutServiceProvider extends ServiceProvider
{
    /**
     * Register the package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/opensearch-scout.php', 'opensearch-scout');

        $this->app->bindIf(ModelFactoryInterface::class, ModelFactory::class);
        $this->app->bindIf(DocumentFactoryInterface::class, DocumentFactory::class);
        $this->app->bindIf(SearchRequestFactoryInterface::class, SearchRequestFactory::class);

        $this->app->singletonIf(DocumentManager::class, function ($app) {
            return new DocumentManager($app->make(ClientBuilderInterface::class)->default());
        });

        $this->app->singletonIf(IndexManager::class, function ($app) {
            return new IndexManager($app->make(ClientBuilderInterface::class)->default());
        });
    }

    /**
     * Bootstrap the package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/opensearch-scout.php' => config_path('opensearch-scout.php'),
        ]);

        $this->app->make(EngineManager::class)->extend('opensearch', function ($app) {
            return $app->make(Engine::class);
        });
    }
}
