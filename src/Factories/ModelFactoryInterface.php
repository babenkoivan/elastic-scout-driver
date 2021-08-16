<?php declare(strict_types=1);

namespace ElasticScoutDriver\Factories;

use ElasticAdapter\Search\SearchResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;

interface ModelFactoryInterface
{
    public function makeFromSearchResponse(
        SearchResponse $searchResponse,
        Builder $builder
    ): Collection;

    public function makeLazyFromSearchResponse(
        SearchResponse $searchResponse,
        Builder $builder
    ): LazyCollection;
}
