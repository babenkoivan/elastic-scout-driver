<?php declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Engine;

use ElasticAdapter\Documents\DocumentManager;
use ElasticAdapter\Search\Hit;
use ElasticAdapter\Search\SearchRequest;
use ElasticScoutDriver\Engine;
use ElasticScoutDriver\Factories\DocumentFactoryInterface;
use ElasticScoutDriver\Factories\ModelFactoryInterface;
use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use ElasticScoutDriver\Tests\App\Client;
use ElasticScoutDriver\Tests\Integration\TestCase;
use Illuminate\Database\Eloquent\Model;
use stdClass;

/**
 * @covers \ElasticScoutDriver\Engine
 *
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

    public function test_empty_model_collection_can_not_be_deleted_from_index(): void
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

    public function test_not_empty_model_collection_can_be_deleted_from_index(): void
    {
        $source = factory(Client::class, rand(6, 10))->create();

        $deleted = $source->slice(0, rand(2, 4))->each(static function (Model $client) {
            $client->forceDelete();
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
        $documentIds = collect($searchResponse->getHits())->map(static function (Hit $hit) {
            return $hit->getDocument()->getId();
        })->all();

        $deleted->each(function (Model $client) use ($documentIds) {
            $this->assertNotContains($client->getKey(), $documentIds);
        });
    }

    public function test_not_found_error_is_ignored_when_models_are_being_deleted_from_index(): void
    {
        $clients = factory(Client::class, rand(2, 10))->create();

        // remove models from index
        $clients->unsearchable();

        $clients->each(function (Model $client) {
            $client->forceDelete();

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

    public function test_models_can_be_soft_deleted_from_index(): void
    {
        // enable soft deletes
        $this->app['config']->set('scout.soft_delete', true);

        $clients = factory(Client::class, rand(2, 10))->create();

        $clients->each(static function (Model $client) {
            $client->delete();
        });

        $searchResponse = $this->documentManager->search(
            $clients->first()->searchableAs(),
            new SearchRequest(['match_all' => new stdClass()])
        );

        collect($searchResponse->getHits())->each(function (Hit $hit) {
            $this->assertSame(1, $hit->getDocument()->getContent()['__soft_deleted']);
        });
    }
}
