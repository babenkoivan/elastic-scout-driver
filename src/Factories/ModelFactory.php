<?php declare(strict_types=1);

namespace Elastic\ScoutDriver\Factories;

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

        $documentIds = $this->pluckDocumentIds($searchResult);
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

        $documentIds = $this->pluckDocumentIds($searchResult);
        /** @var LazyCollection $models */
        $models = $builder->model->queryScoutModelsByIds($builder, $documentIds)->cursor();

        return $this->sortModels($this->filterModels($models, $documentIds), $documentIds);
    }

    private function pluckDocumentIds(SearchResult $searchResult): array
    {
        return $searchResult->hits()->map(static fn (Hit $hit) => $hit->document()->id())->all();
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
        return $models->filter(static fn (Model $model) => in_array($model->getScoutKey(), $documentIds))->values();
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
        return $models->sortBy(static fn (Model $model) => $documentIdPositions[$model->getScoutKey()])->values();
    }
}
