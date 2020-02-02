<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Tests\app;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property string $last_name
 * @property string $phone_number
 * @property string $email
 */
final class Client extends Model
{
    use Searchable;

    public $timestamps = false;

    /**
     * {@inheritDoc}
     */
    public function toSearchableArray()
    {
        return Arr::except($this->toArray(), $this->getKeyName());
    }
}
