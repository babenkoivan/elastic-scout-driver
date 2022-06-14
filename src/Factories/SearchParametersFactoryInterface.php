<?php declare(strict_types=1);

namespace ElasticScoutDriver\Factories;

use Elastic\Adapter\Search\SearchParameters;
use Laravel\Scout\Builder;

interface SearchParametersFactoryInterface
{
    public function makeFromBuilder(Builder $builder, array $options = []): SearchParameters;
}
