<?php declare(strict_types=1);

namespace ElasticScoutDriver;

use Elastic\Adapter\Documents\DocumentManager;
use Elastic\Adapter\Indices\Index;
use Elastic\Adapter\Indices\IndexManager;
use Elastic\Adapter\Search\Hit;
use Elastic\Adapter\Search\SearchResult;
use ElasticScoutDriver\Factories\DocumentFactoryInterface;
use ElasticScoutDriver\Factories\ModelFactoryInterface;
use ElasticScoutDriver\Factories\SearchParametersFactoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use InvalidArgumentException;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine as AbstractEngine;
use stdClass;

class Engine extends AbstractEngine
{
    /**
     * @var bool
     */
    protected $refreshDocuments;
    /**
     * @var DocumentManager
     */
    protected $documentManager;
    /**
     * @var DocumentFactoryInterface
     */
    protected $documentFactory;
    /**
     * @var SearchParametersFactoryInterface
     */
    protected $searchParametersFactory;
    /**
     * @var ModelFactoryInterface
     */
    protected $modelFactory;
    /**
     * @var IndexManager
     */
    protected $indexManager;

    public function __construct(
        DocumentManager $documentManager,
        DocumentFactoryInterface $documentFactory,
        SearchParametersFactoryInterface $searchParametersFactory,
        ModelFactoryInterface $modelFactory,
        IndexManager $indexManager
    ) {
        $this->refreshDocuments = config('elastic.scout_driver.refresh_documents');

        $this->documentManager = $documentManager;
        $this->documentFactory = $documentFactory;
        $this->searchParametersFactory = $searchParametersFactory;
        $this->modelFactory = $modelFactory;
        $this->indexManager = $indexManager;
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

        $this->documentManager->index($index, $documents, $this->refreshDocuments);
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

        $documentIds = $models->map(static function (Model $model) {
            return (string)$model->getScoutKey();
        })->all();

        $this->documentManager->delete($index, $documentIds, $this->refreshDocuments);
    }

    /**
     * {@inheritDoc}
     */
    public function search(Builder $builder)
    {
        $index = $builder->index ?: $builder->model->searchableAs();
        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder);

        return $this->documentManager->search($index, $searchParameters);
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $index = $builder->index ?: $builder->model->searchableAs();

        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder, [
            'perPage' => (int)$perPage,
            'page' => (int)$page,
        ]);

        return $this->documentManager->search($index, $searchParameters);
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param SearchResult $results
     *
     * @return BaseCollection
     */
    public function mapIds($results)
    {
        return $results->hits()->map(static function (Hit $hit) {
            return $hit->document()->id();
        });
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param SearchResult $results
     * @param Model          $model
     *
     * @return EloquentCollection
     */
    public function map(Builder $builder, $results, $model)
    {
        return $this->modelFactory->makeFromSearchResult($results, $builder);
    }

    /**
     * {@inheritDoc}
     */
    public function lazyMap(Builder $builder, $results, $model)
    {
        return $this->modelFactory->makeLazyFromSearchResult($results, $builder);
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param SearchResult $results
     *
     * @return int|null
     */
    public function getTotalCount($results)
    {
        return $results->total();
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

    /**
     * {@inheritDoc}
     */
    public function createIndex($name, array $options = [])
    {
        if (isset($options['primaryKey'])) {
            throw new InvalidArgumentException('It is not possible to change the primary key name');
        }

        $this->indexManager->create(new Index($name));
    }

    /**
     * {@inheritDoc}
     */
    public function deleteIndex($name)
    {
        $this->indexManager->drop($name);
    }
}
