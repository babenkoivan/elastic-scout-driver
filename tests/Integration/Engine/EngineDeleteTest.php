<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Engine;

use ElasticAdapter\Documents\DocumentManager;
use ElasticAdapter\Search\SearchRequest;
use ElasticScoutDriver\Engine;
use ElasticScoutDriver\Factories\DocumentFactoryInterface;
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

        $engine = new Engine($documentManager, resolve(DocumentFactoryInterface::class));
        $engine->delete((new Client())->newCollection());
    }

    public function test_not_empty_model_collection_can_be_deleted(): void
    {
        $clients = factory(Client::class, rand(2, 10))->create();

        $index = $clients->first()->searchableAs();
        $searchAllRequest = new SearchRequest(['match_all' => new stdClass()]);

        // assert that documents are in the index
        $this->assertSame($clients->count(), $this->documentManager->search($index, $searchAllRequest)->getHitsTotal());

        $clients->each(function (Model $model) {
            $model->delete();
        });

        // assert that the index is empty
        $this->assertSame(0, $this->documentManager->search($index, $searchAllRequest)->getHitsTotal());
    }
}
