<?php declare(strict_types=1);

namespace ElasticScoutDriver\Tests\App;

use Carbon\Carbon;
use ElasticScoutDriver\CustomRouting;
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
 * @property Carbon $deleted_at
 */
final class ClientWithRouting extends Model implements CustomRouting
{
    use Searchable;
    use SoftDeletes;

    protected $table = 'clients';

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

    public function getRoutingKey(): string
    {
        return $this->email;
    }
}
