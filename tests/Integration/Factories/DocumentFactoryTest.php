<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Factories;

use ElasticAdapter\Documents\Document;
use ElasticScoutDriver\Factories\DocumentFactory;
use ElasticScoutDriver\Tests\app\Client;
use ElasticScoutDriver\Tests\Integration\TestCase;
use Laravel\Scout\Searchable;

/**
 * @covers \ElasticScoutDriver\Factories\DocumentFactory
 */
final class DocumentFactoryTest extends TestCase
{
    /**
     * @var DocumentFactory
     */
    private $documentFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentFactory = new DocumentFactory();
    }

    public function test_documents_can_be_created_from_models(): void
    {
        $models = factory(Client::class, rand(2, 10))->create();
        $documents = $this->documentFactory->makeFromModels($models);

        for ($i = 0; $i < $models->count(); $i++) {
            /** @var Searchable $model */
            $model = $models->get($i);
            /** @var Document $document */
            $document = $documents->get($i);

            $this->assertSame((string)$model->getScoutKey(), $document->getId());
            $this->assertSame($model->toSearchableArray(), $document->getContent());
        }
    }
}
