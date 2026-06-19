<?php

namespace DirectoryTree\OpenSearchScoutDriver\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Client extends Model
{
    use Searchable;
    use SoftDeletes;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes hidden from array serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
    ];

    /**
     * The model attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'id' => 1,
        'name' => 'John',
        'email' => 'john@example.com',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $attributes = $this->toArray();

        unset($attributes[$this->getKeyName()]);

        return $attributes;
    }
}
