<?php

namespace DirectoryTree\OpenSearchScoutDriver;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\CursorPaginator as BaseCursorPaginator;
use UnexpectedValueException;

class CursorPaginator extends BaseCursorPaginator
{
    /**
     * The cursor parameter containing OpenSearch hit sort values.
     */
    public const SEARCH_AFTER_PARAMETER = '_search_after';

    /**
     * @param  array<string, array<int, mixed>>  $searchAfter
     */
    protected array $searchAfter = [];

    /**
     * Get the cursor parameters for a given item.
     */
    public function getParametersForItem(mixed $item): array
    {
        /** @var Model $item */
        $item = $item instanceof JsonResource ? $item->resource : $item;

        if (! $item instanceof Model) {
            throw new UnexpectedValueException('OpenSearch cursor pagination only supports Eloquent models.');
        }

        if (! method_exists($item, 'getScoutKey')) {
            throw new UnexpectedValueException('OpenSearch cursor pagination only supports Eloquent models that use the Laravel Scout Searchable trait.');
        }

        if (! array_key_exists($key = $item->getScoutKey(), $this->searchAfter)) {
            throw new UnexpectedValueException(sprintf('Unable to resolve OpenSearch search_after values for model [%s] with scout key [%s].', $item::class, $key));
        }

        return [self::SEARCH_AFTER_PARAMETER => $this->searchAfter[$key]];
    }
}
