<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Engine;

use ElasticAdapter\Documents\DocumentManager;
use ElasticAdapter\Search\Hit;
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
        $source = factory(Client::class, rand(6, 10))->create();

        $deleted = $source->slice(0, rand(2, 4))->each(function (Model $client) {
            $client->delete();
        });

        $searchResponse = $this->documentManager->search(
            $source->first()->searchableAs(),
            new SearchRequest(['match_all' => new stdClass()])
        );

        // assert that index has less documents
        $this->assertSame(
            $source->count() - $deleted->count(),
            $searchResponse->getHitsTotal()
        );

        // assert that index doesn't have documents with ids corresponding to the deleted models
        $documentIds = collect($searchResponse->getHits())->map(function (Hit $hit) {
            return $hit->getDocument()->getId();
        })->all();

        $deleted->each(function (Model $client) use ($documentIds) {
            $this->assertNotContains($client->id, $documentIds);
        });
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

    public function test_models_can_be_flushed_from_index(): void
    {
        $clients = factory(Client::class, rand(2, 10))->create();

        Client::removeAllFromSearch();

        $searchResponse = $this->documentManager->search(
            $clients->first()->searchableAs(),
            new SearchRequest(['match_all' => new stdClass()])
        );

        // assert that index is empty
        $this->assertSame(0, $searchResponse->getHitsTotal());
    }
}
