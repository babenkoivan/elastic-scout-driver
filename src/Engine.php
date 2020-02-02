<?php
declare(strict_types=1);

namespace ElasticScoutDriver;

use ElasticAdapter\Documents\DocumentManager;
use ElasticScoutDriver\Factories\DocumentFactoryInterface;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine as AbstractEngine;

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

    public function __construct(
        DocumentManager $documentManager,
        DocumentFactoryInterface $documentFactory
    ) {
        $this->refreshDocuments = config('elastic.scout_driver.refresh_documents');

        $this->documentManager = $documentManager;
        $this->documentFactory = $documentFactory;
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
        // TODO: Implement search() method.
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        // TODO: Implement paginate() method.
    }

    /**
     * {@inheritDoc}
     */
    public function mapIds($results)
    {
        // TODO: Implement mapIds() method.
    }

    /**
     * {@inheritDoc}
     */
    public function map(Builder $builder, $results, $model)
    {
        // TODO: Implement map() method.
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalCount($results)
    {
        // TODO: Implement getTotalCount() method.
    }

    /**
     * {@inheritDoc}
     */
    public function flush($model)
    {
        // TODO: Implement flush() method.
    }
}
