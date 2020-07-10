<?php declare(strict_types=1);

namespace ElasticScoutDriver\Factories;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as BaseCollection;

interface DocumentFactoryInterface
{
    public function makeFromModels(EloquentCollection $models): BaseCollection;
}
