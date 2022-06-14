<?php declare(strict_types=1);

namespace Elastic\ScoutDriver\Factories;

use Illuminate\Support\Collection;

interface DocumentFactoryInterface
{
    public function makeFromModels(Collection $models): Collection;
}
