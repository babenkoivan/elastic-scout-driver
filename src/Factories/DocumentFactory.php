<?php declare(strict_types=1);

namespace ElasticScoutDriver\Factories;

use ElasticAdapter\Documents\Document;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as BaseCollection;
use UnexpectedValueException;

final class DocumentFactory implements DocumentFactoryInterface
{
    public function makeFromModels(EloquentCollection $models): BaseCollection
    {
        return $models->map(static function (Model $model) {
            if (
                in_array(SoftDeletes::class, class_uses_recursive(get_class($model))) &&
                config('scout.soft_delete', false)
            ) {
                $model->pushSoftDeleteMetadata();
            }

            $documentId = (string)$model->getScoutKey();
            $documentContent = array_merge($model->scoutMetadata(), $model->toSearchableArray());

            if (array_key_exists('_id', $documentContent)) {
                throw new UnexpectedValueException(sprintf(
                    '_id is not allowed in the document content. Please, make sure the field is not returned by ' .
                    'the %1$s::toSearchableArray or %1$s::scoutMetadata methods.',
                    class_basename($model)
                ));
            }

            return new Document($documentId, $documentContent);
        })->toBase();
    }
}
