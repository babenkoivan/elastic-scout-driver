<?php declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Factories;

use ElasticScoutDriver\Factories\DocumentFactory;
use ElasticScoutDriver\Tests\App\Client;
use ElasticScoutDriver\Tests\Integration\TestCase;
use UnexpectedValueException;

/**
 * @covers \ElasticScoutDriver\Factories\DocumentFactory
 *
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
            $model = $clients->get($i);
            $document = $documents->get($i);

            $this->assertSame((string)$model->getScoutKey(), $document->getId());
            $this->assertSame($model->toSearchableArray(), $document->getContent());
        }
    }

    public function test_an_exception_is_thrown_when_document_content_has_restricted_fields(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->expectExceptionMessage(
            '_id is not allowed in the document content. Please, make sure the field is not returned ' .
            'by the Client::toSearchableArray or Client::scoutMetadata methods.'
        );

        $clients = factory(Client::class, rand(2, 10))->create();

        // add restricted _id field in the scout metadata
        $clients->each(static function (Client $client) {
            $client->withScoutMetadata('_id', random_int(0, 1000));
        });

        $this->documentFactory->makeFromModels($clients);
    }
}
