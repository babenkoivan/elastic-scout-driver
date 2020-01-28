<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Tests\app;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $last_name
 * @property string $phone_number
 * @property string $email
 */
final class Client extends Model
{
    public $timestamps = false;
}
