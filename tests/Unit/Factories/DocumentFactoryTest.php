<?php

use DirectoryTree\OpenSearchAdapter\Documents\Document;
use DirectoryTree\OpenSearchScoutDriver\Factories\DocumentFactory;
use DirectoryTree\OpenSearchScoutDriver\Tests\Fixtures\Client;
use Illuminate\Database\Eloquent\Collection;

it('creates documents from models', function () {
    $models = new Collection([
        new Client(['id' => 1, 'name' => 'John']),
        new Client(['id' => 2, 'name' => 'Jane']),
    ]);

    $documents = (new DocumentFactory)->makeFromModels($models);

    expect($documents)->toHaveCount(2)
        ->and($documents->first())->toBeInstanceOf(Document::class)
        ->and($documents->first()->id())->toBe('1')
        ->and($documents->first()->content())->toBe(['name' => 'John', 'email' => 'john@example.com']);
});

it('rejects restricted document fields', function () {
    $model = new class extends Client
    {
        public function toSearchableArray(): array
        {
            return ['_id' => 'restricted'];
        }
    };

    (new DocumentFactory)->makeFromModels(new Collection([$model]));
})->throws(UnexpectedValueException::class);
