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
     * Create OpenSearch documents from the given model collection.
     */
    public function makeFromModels(Collection $models): Collection
    {
        return $models->map(function (Model $model) {
            if (
                config('scout.soft_delete', false) &&
                in_array(SoftDeletes::class, class_uses_recursive($model), true)
            ) {
                $model->pushSoftDeleteMetadata();
            }

            $documentId = (string) $model->getScoutKey();
            $documentContent = array_merge($model->scoutMetadata(), $model->toSearchableArray());

            if (array_key_exists('_id', $documentContent)) {
                throw new UnexpectedValueException(sprintf(
                    '_id is not allowed in the document content. Please, make sure the field is not returned by '.
                    'the %1$s::toSearchableArray or %1$s::scoutMetadata methods.',
                    class_basename($model)
                ));
            }

            return new Document($documentId, $documentContent);
        });
    }
}
