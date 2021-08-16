<?php declare(strict_types=1);

namespace ElasticScoutDriver\Factories;

use Illuminate\Support\Collection;

interface DocumentFactoryInterface
{
    public function makeFromModels(Collection $models): Collection;
}
