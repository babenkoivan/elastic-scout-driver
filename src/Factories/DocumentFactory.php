<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Factories;

use ElasticAdapter\Documents\Document;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as BaseCollection;
use Laravel\Scout\Searchable;

final class DocumentFactory implements DocumentFactoryInterface
{
    public function makeFromModels(EloquentCollection $models): BaseCollection
    {
        return $models->map(function (Model $model) {
            /** @var Searchable $model */
            if (
                in_array(SoftDeletes::class, class_uses_recursive(get_class($model))) &&
                config('scout.soft_delete', false)
            ) {
                $model->pushSoftDeleteMetadata();
            }

            return new Document(
                (string)$model->getScoutKey(),
                array_merge($model->scoutMetadata(), $model->toSearchableArray())
            );
        })->toBase();
    }
}
