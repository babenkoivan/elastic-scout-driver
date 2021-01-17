<?php declare(strict_types=1);

namespace ElasticScoutDriver;

use ElasticAdapter\Documents\DocumentManager;
use ElasticAdapter\Search\Hit;
use ElasticAdapter\Search\SearchResponse;
use ElasticScoutDriver\Factories\DocumentFactoryInterface;
use ElasticScoutDriver\Factories\ModelFactoryInterface;
use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine as AbstractEngine;
use stdClass;

final class Engine extends AbstractEngine
{
    /**
     * @var bool
     */
    private $refreshDocuments;
    /**
     * @var DocumentManager
     */
    private $documentManager;
    /**
     * @var DocumentFactoryInterface
     */
    private $documentFactory;
    /**
     * @var SearchRequestFactoryInterface
     */
    private $searchRequestFactory;
    /**
     * @var ModelFactoryInterface
     */
    private $modelFactory;

    public function __construct(
        DocumentManager $documentManager,
        DocumentFactoryInterface $documentFactory,
        SearchRequestFactoryInterface $searchRequestFactory,
        ModelFactoryInterface $modelFactory
    ) {
        $this->refreshDocuments = config('elastic.scout_driver.refresh_documents');

        $this->documentManager = $documentManager;
        $this->documentFactory = $documentFactory;
        $this->searchRequestFactory = $searchRequestFactory;
        $this->modelFactory = $modelFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $index = $models->first()->searchableAs();
        $documents = $this->documentFactory->makeFromModels($models);

        $this->documentManager->index($index, $documents->all(), $this->refreshDocuments);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $index = $models->first()->searchableAs();
        $documents = $this->documentFactory->makeFromModels($models);

        $this->documentManager->delete($index, $documents->all(), $this->refreshDocuments);
    }

    /**
     * {@inheritDoc}
     */
    public function search(Builder $builder)
    {
        $index = $builder->model->searchableAs();
        $searchRequest = $this->searchRequestFactory->makeFromBuilder($builder);

        return $this->documentManager->search($index, $searchRequest);
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $index = $builder->model->searchableAs();

        $searchRequest = $this->searchRequestFactory->makeFromBuilder($builder, [
            'perPage' => (int)$perPage,
            'page' => (int)$page,
        ]);

        return $this->documentManager->search($index, $searchRequest);
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param SearchResponse $results
     *
     * @return BaseCollection
     */
    public function mapIds($results)
    {
        return collect($results->getHits())->map(static function (Hit $hit) {
            return $hit->getDocument()->getId();
        });
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param SearchResponse $results
     * @param Model          $model
     *
     * @return EloquentCollection
     */
    public function map(Builder $builder, $results, $model)
    {
        return $this->modelFactory->makeFromSearchResponseUsingBuilder($results, $builder);
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param SearchResponse $results
     *
     * @return int|null
     */
    public function getTotalCount($results)
    {
        return $results->getHitsTotal();
    }

    /**
     * {@inheritDoc}
     */
    public function flush($model)
    {
        $index = $model->searchableAs();
        $query = ['match_all' => new stdClass()];

        $this->documentManager->deleteByQuery($index, $query, $this->refreshDocuments);
    }
}
