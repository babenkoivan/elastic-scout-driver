<?php declare(strict_types=1);

namespace Elastic\ScoutDriver;

use Elastic\Adapter\Documents\DocumentManager;
use Elastic\Adapter\Indices\Index;
use Elastic\Adapter\Indices\IndexManager;
use Elastic\Adapter\Search\Hit;
use Elastic\Adapter\Search\SearchResult;
use Elastic\ScoutDriver\Factories\DocumentFactoryInterface;
use Elastic\ScoutDriver\Factories\ModelFactoryInterface;
use Elastic\ScoutDriver\Factories\SearchParametersFactoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine as AbstractEngine;
use stdClass;

class Engine extends AbstractEngine
{
    protected bool $refreshDocuments;
    protected DocumentManager $documentManager;
    protected DocumentFactoryInterface $documentFactory;
    protected SearchParametersFactoryInterface $searchParametersFactory;
    protected ModelFactoryInterface $modelFactory;
    protected IndexManager $indexManager;

    public function __construct(
        DocumentManager $documentManager,
        DocumentFactoryInterface $documentFactory,
        SearchParametersFactoryInterface $searchParametersFactory,
        ModelFactoryInterface $modelFactory,
        IndexManager $indexManager
    ) {
        $this->refreshDocuments = (bool)config('elastic.scout_driver.refresh_documents');

        $this->documentManager = $documentManager;
        $this->documentFactory = $documentFactory;
        $this->searchParametersFactory = $searchParametersFactory;
        $this->modelFactory = $modelFactory;
        $this->indexManager = $indexManager;
    }

    /**
     * @param EloquentCollection $models
     *
     * @return void
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
     * @param EloquentCollection $models
     *
     * @return void
     */
    public function delete($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $index = $models->first()->searchableAs();
        $documentIds = $models->map(static fn (Model $model) => (string)$model->getScoutKey())->all();

        $this->documentManager->delete($index, $documentIds, $this->refreshDocuments);
    }

    /**
     * @return SearchResult
     */
    public function search(Builder $builder)
    {
        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder);
        return $this->documentManager->search($searchParameters);
    }

    /**
     * @param int $perPage
     * @param int $page
     *
     * @return SearchResult
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder, [
            'perPage' => (int)$perPage,
            'page' => (int)$page,
        ]);

        return $this->documentManager->search($searchParameters);
    }

    /**
     * @param SearchResult $results
     *
     * @return BaseCollection
     */
    public function mapIds($results)
    {
        return $results->hits()->map(static fn (Hit $hit) => $hit->document()->id());
    }

    /**
     * @param SearchResult $results
     * @param Model        $model
     *
     * @return EloquentCollection
     */
    public function map(Builder $builder, $results, $model)
    {
        return $this->modelFactory->makeFromSearchResult($results, $builder);
    }

    /**
     * @param SearchResult $results
     * @param Model        $model
     *
     * @return LazyCollection
     */
    public function lazyMap(Builder $builder, $results, $model)
    {
        return $this->modelFactory->makeLazyFromSearchResult($results, $builder);
    }

    /**
     * @param SearchResult $results
     *
     * @return int|null
     */
    public function getTotalCount($results)
    {
        return $results->total();
    }

    /**
     * @param Model $model
     */
    public function flush($model)
    {
        $index = $model->searchableAs();
        $query = ['match_all' => new stdClass()];

        $this->documentManager->deleteByQuery($index, $query, $this->refreshDocuments);
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function createIndex($name, array $options = [])
    {
        if (isset($options['primaryKey'])) {
            throw new InvalidArgumentException('It is not possible to change the primary key name');
        }

        $this->indexManager->create(new Index($name));
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function deleteIndex($name)
    {
        $this->indexManager->drop($name);
    }
}
