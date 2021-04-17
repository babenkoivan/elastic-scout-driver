<?php declare(strict_types=1);

use ElasticScoutDriver\Tests\App\ClientWithRouting;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(ClientWithRouting::class, static function (Faker $faker) {
    return [
        'name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'phone_number' => $faker->unique()->e164PhoneNumber,
        'email' => $faker->unique()->email,
    ];
});
