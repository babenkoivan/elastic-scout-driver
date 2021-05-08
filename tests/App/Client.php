<?php declare(strict_types=1);

namespace ElasticScoutDriver\Tests\App;

use Carbon\Carbon;
use ElasticScoutDriverPlus\ShardRouting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;

/**
 * @property int    $id
 * @property string $name
 * @property string $last_name
 * @property string $phone_number
 * @property string $email
 * @property boolean $use_shard_routing
 * @property Carbon $deleted_at
 */
final class Client extends Model
{
    use Searchable;
    use SoftDeletes;
    use ShardRouting;

    public $timestamps = false;

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * @return array
     */
    public function toSearchableArray()
    {
        return Arr::except($this->toArray(), [$this->getKeyName()]);
    }

    /**
     * Get the name of the index associated with the model.
     *
     * @return string
     */
    public function searchableAs()
    {
        return $this->use_shard_routing ? 'clients_sharded' : 'clients';
    }

    public function getRoutingPath(): string
    {
        if ($this->use_shard_routing) {
            return 'last_name';
        }

        return $this->getScoutKeyName();
    }
}
