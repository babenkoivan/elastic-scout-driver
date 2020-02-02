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
 * @uses   \ElasticScoutDriver\Engine
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

    public function test_document_collection_can_be_made_from_model_collection(): void
    {
        $clients = factory(Client::class, rand(2, 10))->create();
        $documents = $this->documentFactory->makeFromModels($clients);

        for ($i = 0; $i < $clients->count(); $i++) {
            /** @var Searchable $model */
            $model = $clients->get($i);
            /** @var Document $document */
            $document = $documents->get($i);

            $this->assertSame((string)$model->getScoutKey(), $document->getId());
            $this->assertSame($model->toSearchableArray(), $document->getContent());
        }
    }
}
