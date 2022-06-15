<?php declare(strict_types=1);

use Elastic\ScoutDriver\Tests\App\Client;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(Client::class, static fn (Faker $faker) => [
    'name' => $faker->firstName,
    'last_name' => $faker->lastName,
    'phone_number' => $faker->unique()->e164PhoneNumber,
    'email' => $faker->unique()->email,
]);
