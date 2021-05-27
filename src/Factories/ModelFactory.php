<?php declare(strict_types=1);

namespace ElasticScoutDriver\Factories;

use ElasticAdapter\Search\Hit;
use ElasticAdapter\Search\SearchResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;

class ModelFactory implements ModelFactoryInterface
{
    public function makeFromSearchResponseUsingBuilder(
        SearchResponse $searchResponse,
        Builder $builder
    ): Collection {
        if (!$searchResponse->getHitsTotal()) {
            return $builder->model->newCollection();
        }

        $documentIds = $this->extractDocumentIdsFromSearchResponse($searchResponse);
        $documentIdPositions = array_flip($documentIds);

        return $builder->model->getScoutModelsByIds($builder, $documentIds)
            ->filter(static function (Model $model) use ($documentIds) {
                return in_array($model->getScoutKey(), $documentIds);
            })
            ->sortBy(static function (Model $model) use ($documentIdPositions) {
                return $documentIdPositions[$model->getScoutKey()];
            })
            ->values();
    }

    public function makeLazyFromSearchResponseUsingBuilder(
        SearchResponse $searchResponse,
        Builder $builder
    ): LazyCollection {
        if (!$searchResponse->getHitsTotal()) {
            return LazyCollection::make($builder->model->newCollection());
        }

        $documentIds = $this->extractDocumentIdsFromSearchResponse($searchResponse);
        $documentIdPositions = array_flip($documentIds);

        return $builder->model->queryScoutModelsByIds($builder, $documentIds)
            ->cursor()
            ->filter(static function (Model $model) use ($documentIds) {
                return in_array($model->getScoutKey(), $documentIds);
            })
            ->sortBy(static function (Model $model) use ($documentIdPositions) {
                return $documentIdPositions[$model->getScoutKey()];
            })
            ->values();
    }

    private function extractDocumentIdsFromSearchResponse(SearchResponse $searchResponse): array
    {
        return collect($searchResponse->getHits())->map(static function (Hit $hit) {
            return $hit->getDocument()->getId();
        })->all();
    }
}
