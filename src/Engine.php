<?php
declare(strict_types=1);

namespace ElasticScoutDriver;

use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine as AbstractEngine;

final class Engine extends AbstractEngine
{
    /**
     * @var bool
     */
    private $refreshDocuments;

    public function __construct()
    {
        $this->refreshDocuments = config('elastic.scout_driver.refresh_documents');
    }

    /**
     * {@inheritDoc}
     */
    public function update($models)
    {
        // TODO: Implement update() method.
    }

    /**
     * {@inheritDoc}
     */
    public function delete($models)
    {
        // TODO: Implement delete() method.
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
