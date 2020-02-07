<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Engine;

use ElasticAdapter\Documents\DocumentManager;
use ElasticAdapter\Search\SearchRequest;
use ElasticScoutDriver\Engine;
use ElasticScoutDriver\Factories\DocumentFactoryInterface;
use ElasticScoutDriver\Factories\ModelFactoryInterface;
use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use ElasticScoutDriver\Tests\app\Client;
use ElasticScoutDriver\Tests\Integration\TestCase;
use Illuminate\Database\Eloquent\Model;
use stdClass;

/**
 * @covers \ElasticScoutDriver\Engine
 * @uses   \ElasticScoutDriver\Factories\DocumentFactory
 */
final class EngineDeleteTest extends TestCase
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = resolve(DocumentManager::class);
    }

    public function test_empty_model_collection_can_not_be_deleted(): void
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->never())->method('delete');

        $engine = new Engine(
            $documentManager,
            resolve(DocumentFactoryInterface::class),
            resolve(SearchRequestFactoryInterface::class),
            resolve(ModelFactoryInterface::class)
        );

        $engine->delete((new Client())->newCollection());
    }

    public function test_not_empty_model_collection_can_be_deleted(): void
    {
        $clients = factory(Client::class, rand(2, 10))->create();

        $index = $clients->first()->searchableAs();
        $searchAllRequest = new SearchRequest(['match_all' => new stdClass()]);

        // assert that documents are in the index
        $this->assertSame($clients->count(), $this->documentManager->search($index, $searchAllRequest)->getHitsTotal());

        $clients->each(function (Model $client) {
            $client->delete();
        });

        // assert that the index is empty
        $this->assertSame(0, $this->documentManager->search($index, $searchAllRequest)->getHitsTotal());
    }

    public function test_not_found_error_is_ignored_when_models_are_being_deleted(): void
    {
        $clients = factory(Client::class, rand(2, 10))->create();

        // remove models from index
        $clients->unsearchable();

        $clients->each(function (Model $client) {
            $client->delete();

            $this->assertDatabaseMissing(
                $client->getTable(),
                [$client->getKeyName() => $client->getKey()]
            );
        });
    }
}
