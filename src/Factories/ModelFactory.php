<?php declare(strict_types=1);

namespace ElasticScoutDriver\Factories;

use Elastic\Adapter\Search\Hit;
use Elastic\Adapter\Search\SearchResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;

class ModelFactory implements ModelFactoryInterface
{
    public function makeFromSearchResult(
        SearchResult $searchResult,
        Builder $builder
    ): Collection {
        if (!$searchResult->total()) {
            return $builder->model->newCollection();
        }

        $documentIds = $this->pluckDocumentIdsFromSearchResult($searchResult);
        /** @var Collection $models */
        $models = $builder->model->getScoutModelsByIds($builder, $documentIds);

        return $this->sortModels($this->filterModels($models, $documentIds), $documentIds);
    }

    public function makeLazyFromSearchResult(
        SearchResult $searchResult,
        Builder $builder
    ): LazyCollection {
        if (!$searchResult->total()) {
            return LazyCollection::make($builder->model->newCollection());
        }

        $documentIds = $this->pluckDocumentIdsFromSearchResult($searchResult);
        /** @var LazyCollection $models */
        $models = $builder->model->queryScoutModelsByIds($builder, $documentIds)->cursor();

        return $this->sortModels($this->filterModels($models, $documentIds), $documentIds);
    }

    private function pluckDocumentIdsFromSearchResult(SearchResult $searchResult): array
    {
        return $searchResult->hits()->map(static function (Hit $hit) {
            return $hit->document()->id();
        })->all();
    }

    /**
     * @template T
     *
     * @param T $models
     *
     * @return T
     */
    private function filterModels($models, array $documentIds)
    {
        return $models->filter(static function (Model $model) use ($documentIds) {
            return in_array($model->getScoutKey(), $documentIds);
        })->values();
    }

    /**
     * @template T
     *
     * @param T $models
     *
     * @return T
     */
    private function sortModels($models, array $documentIds)
    {
        $documentIdPositions = array_flip($documentIds);

        return $models->sortBy(static function (Model $model) use ($documentIdPositions) {
            return $documentIdPositions[$model->getScoutKey()];
        })->values();
    }
}
