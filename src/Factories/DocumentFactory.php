<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Factories;

use ElasticAdapter\Documents\Document;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Laravel\Scout\Searchable;

final class DocumentFactory implements DocumentFactoryInterface
{
    public function makeFromModels(EloquentCollection $models): BaseCollection
    {
        return $models->map(function (Model $model) {
            /** @var Searchable $model */
            return new Document((string)$model->getScoutKey(), $model->toSearchableArray());
        })->toBase();
    }
}
