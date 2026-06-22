<?php

use DirectoryTree\OpenSearchAdapter\Indices\IndexManager;
use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchScoutDriver\Tests\Fixtures\Client;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\EngineManager;

beforeEach(function (): void {
    config()->set('scout.prefix', sprintf('scout_integration_%s_', bin2hex(random_bytes(4))));

    Schema::create('clients', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamp('deleted_at')->nullable();
    });

    Client::query()->insert([
        ['id' => 1, 'name' => 'John Smith', 'email' => 'john@example.com'],
        ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ['id' => 3, 'name' => 'Taylor Otwell', 'email' => 'taylor@example.com'],
    ]);

    $this->indexName = (new Client)->searchableAs();
    $this->indexManager = app(IndexManager::class);

    if ($this->indexManager->exists($this->indexName)) {
        $this->indexManager->delete($this->indexName);
    }

    app(EngineManager::class)->engine('opensearch')->createIndex($this->indexName);

    $this->indexManager->putMapping($this->indexName, (new Mapping)
        ->text('name', ['fielddata' => true])
        ->keyword('email')
        ->integer('__soft_deleted'));
});

afterEach(function (): void {
    if (isset($this->indexManager, $this->indexName) && $this->indexManager->exists($this->indexName)) {
        $this->indexManager->delete($this->indexName);
    }

    Schema::dropIfExists('clients');
});

it('indexes searches paginates deletes and flushes models against opensearch', function (): void {
    $engine = app(EngineManager::class)->engine('opensearch');

    $engine->update(Client::query()->orderBy('id')->get());

    expect(Client::search('Smith')->get()->pluck('id')->all())->toBe([1, 2])
        ->and(Client::search('')->where('email', 'jane@example.com')->get()->pluck('id')->all())->toBe([2])
        ->and(Client::search('')->whereIn('email', ['john@example.com', 'taylor@example.com'])->get()->pluck('id')->all())->toBe([1, 3])
        ->and(Client::search('')->orderBy('name', 'asc')->paginate(2)->pluck('id')->all())->toBe([2, 1]);

    $engine->delete(Client::query()->whereKey(1)->get());

    expect(Client::search('John')->get())->toHaveCount(0);

    $engine->flush(new Client);

    expect(Client::search('')->get())->toHaveCount(0);
});

it('filters stale opensearch hits when database records no longer exist', function (): void {
    $engine = app(EngineManager::class)->engine('opensearch');

    $engine->update(Client::query()->orderBy('id')->get());

    Client::query()->whereKey(2)->forceDelete();

    expect(Client::search('Jane')->get())->toHaveCount(0);
});

it('indexes and filters soft deleted models when scout soft deletes are enabled', function (): void {
    config()->set('scout.soft_delete', true);

    $engine = app(EngineManager::class)->engine('opensearch');

    Client::query()->whereKey(3)->delete();

    $engine->update(Client::withTrashed()->orderBy('id')->get());

    expect(Client::search('')->get()->pluck('id')->sort()->values()->all())->toBe([1, 2])
        ->and(Client::search('')->withTrashed()->get()->pluck('id')->sort()->values()->all())->toBe([1, 2, 3])
        ->and(Client::search('')->onlyTrashed()->get()->pluck('id')->sort()->values()->all())->toBe([3]);
});
