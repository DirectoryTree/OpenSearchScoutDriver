<?php

namespace DirectoryTree\OpenSearchScoutDriver\Factories;

use DirectoryTree\OpenSearchAdapter\Documents\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use UnexpectedValueException;

/**
 * Creates OpenSearch documents from Scout models.
 */
class DocumentFactory implements DocumentFactoryInterface
{
    /**
     * Create a new document factory instance.
     */
    public function __construct(
        protected bool $softDelete = false,
    ) {}

    /**
     * Create OpenSearch documents from the given model collection.
     */
    public function makeFromModels(Collection $models): Collection
    {
        return $models->map(function (Model $model) {
            if ($this->softDelete && $this->isSoftDeletable($model)) {
                $model->pushSoftDeleteMetadata();
            }

            $source = $this->makeDocumentSource($model);

            $this->assertValidDocumentSource($model, $source);

            return new Document((string) $model->getScoutKey(), $source);
        });
    }

    /**
     * Assert that the given document source is valid.
     *
     * @throws \UnexpectedValueException
     */
    protected function assertValidDocumentSource(Model $model, array $source): void
    {
        if (array_key_exists('_id', $source)) {
            throw new UnexpectedValueException(sprintf(
                '_id is not allowed in the document source. Please, make sure the field is not returned by '.
                'the %1$s::toSearchableArray or %1$s::scoutMetadata methods.',
                class_basename($model)
            ));
        }
    }

    /**
     * Create the document source from the given model.
     */
    protected function makeDocumentSource(Model $model): array
    {
        return array_merge(
            $model->scoutMetadata(),
            $model->toSearchableArray()
        );
    }

    /**
     * Determine if the given model uses soft deletes.
     */
    protected function isSoftDeletable(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }
}
