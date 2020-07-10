<?php declare(strict_types=1);

namespace ElasticScoutDriver\Tests\App;

use Carbon\Carbon;
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
final class Client extends Model
{
    use Searchable;
    use SoftDeletes;

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
}
