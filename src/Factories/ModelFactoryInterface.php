<?php declare(strict_types=1);

namespace ElasticScoutDriver\Factories;

use ElasticAdapter\Search\SearchResponse;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Builder;

interface ModelFactoryInterface
{
    public function makeFromSearchResponseUsingBuilder(SearchResponse $searchResponse, Builder $builder): Collection;
}
