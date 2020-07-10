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
use stdClass;

/**
 * @covers \ElasticScoutDriver\Engine
 *
 * @uses   \ElasticScoutDriver\Factories\DocumentFactory
 */
final class EngineUpdateTest extends TestCase
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

    public function test_empty_model_collection_can_not_be_indexed(): void
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->never())->method('index');

        $engine = new Engine(
            $documentManager,
            resolve(DocumentFactoryInterface::class),
            resolve(SearchRequestFactoryInterface::class),
            resolve(ModelFactoryInterface::class)
        );

        $engine->update((new Client())->newCollection());
    }

    public function test_not_empty_model_collection_can_be_indexed(): void
    {
        $clients = factory(Client::class, rand(2, 10))->create();

        $searchResponse = $this->documentManager->search(
            $clients->first()->searchableAs(),
            new SearchRequest(['match_all' => new stdClass()])
        );

        // assert that the amount of created models corresponds number of found documents
        $this->assertSame($clients->count(), $searchResponse->getHitsTotal());

        // assert that the same model ids are in the index
        $clientIds = $clients->pluck($clients->first()->getKeyName())->all();

        $documentIds = collect($searchResponse->getHits())->map(static function (Hit $hit) {
            return $hit->getDocument()->getId();
        })->all();

        $this->assertEquals($clientIds, $documentIds);
    }

    public function test_metadata_is_indexed_when_soft_deletes_are_enabled(): void
    {
        // enable soft deletes
        $this->app['config']->set('scout.soft_delete', true);

        $clients = factory(Client::class, rand(2, 10))->create();

        $searchResponse = $this->documentManager->search(
            $clients->first()->searchableAs(),
            new SearchRequest(['match_all' => new stdClass()])
        );

        collect($searchResponse->getHits())->each(function (Hit $hit) {
            $this->assertSame(0, $hit->getDocument()->getContent()['__soft_deleted']);
        });
    }
}
