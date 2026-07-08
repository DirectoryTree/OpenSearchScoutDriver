<?php

namespace DirectoryTree\OpenSearchScoutDriver;

use DirectoryTree\OpenSearchAdapter\Documents\DocumentManager;
use DirectoryTree\OpenSearchAdapter\Documents\DocumentManagerInterface;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManager;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManagerInterface;
use DirectoryTree\OpenSearchClient\OpenSearchManager;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\ModelFactoryInterface;
use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactory;
use DirectoryTree\OpenSearchScoutDriver\Factories\SearchRequestFactoryInterface;
use Illuminate\Contracts\Foundation\Application;
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

        $this->app->bind(ModelFactoryInterface::class, ModelFactory::class);
        $this->app->bind(SearchRequestFactoryInterface::class, SearchRequestFactory::class);

        $this->app->bind(DocumentFactoryInterface::class, function (Application $app) {
            return new DocumentFactory($app->make('config')->get('scout.soft_delete', false));
        });

        $this->app->singleton(DocumentManagerInterface::class, function (Application $app) {
            return new DocumentManager($app->make(OpenSearchManager::class)->default());
        });

        $this->app->singleton(IndexManagerInterface::class, function (Application $app) {
            return new IndexManager($app->make(OpenSearchManager::class)->default());
        });

        $this->app->bind(Engine::class, function (Application $app) {
            return new Engine(
                $app->make(ModelFactoryInterface::class),
                $app->make(IndexManagerInterface::class),
                $app->make(DocumentManagerInterface::class),
                $app->make(DocumentFactoryInterface::class),
                $app->make(SearchRequestFactoryInterface::class),
                $app->make('config')->get('opensearch-scout.refresh_documents', false),
            );
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

        $this->app->make(EngineManager::class)->extend('opensearch', function (Application $app) {
            return $app->make(Engine::class);
        });
    }
}
