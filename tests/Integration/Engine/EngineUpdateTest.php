<?php declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Engine;

use Elastic\Adapter\Documents\DocumentManager;
use Elastic\Adapter\Indices\IndexManager;
use Elastic\Adapter\Search\Hit;
use Elastic\Adapter\Search\SearchParameters;
use ElasticScoutDriver\Engine;
use ElasticScoutDriver\Factories\DocumentFactoryInterface;
use ElasticScoutDriver\Factories\ModelFactoryInterface;
use ElasticScoutDriver\Factories\SearchParametersFactoryInterface;
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
            resolve(SearchParametersFactoryInterface::class),
            resolve(ModelFactoryInterface::class),
            resolve(IndexManager::class)
        );

        $engine->update((new Client())->newCollection());
    }

    public function test_not_empty_model_collection_can_be_indexed(): void
    {
        $clients = factory(Client::class, rand(2, 10))->create();

        $searchParameters = (new SearchParameters())->query(['match_all' => new stdClass()]);
        $searchResult = $this->documentManager->search($clients->first()->searchableAs(), $searchParameters);

        // assert that the amount of created models corresponds number of found documents
        $this->assertSame($clients->count(), $searchResult->total());

        // assert that the same model ids are in the index
        $clientIds = $clients->pluck($clients->first()->getKeyName())->all();

        $documentIds = $searchResult->hits()->map(static function (Hit $hit) {
            return $hit->document()->id();
        })->all();

        $this->assertEquals($clientIds, $documentIds);
    }

    public function test_metadata_is_indexed_when_soft_deletes_are_enabled(): void
    {
        // enable soft deletes
        $this->app['config']->set('scout.soft_delete', true);

        $clients = factory(Client::class, rand(2, 10))->create();

        $searchParameters = (new SearchParameters())->query(['match_all' => new stdClass()]);
        $searchResult = $this->documentManager->search($clients->first()->searchableAs(), $searchParameters);

        $searchResult->hits()->each(function (Hit $hit) {
            $this->assertSame(0, $hit->document()->content('__soft_deleted'));
        });
    }
}
