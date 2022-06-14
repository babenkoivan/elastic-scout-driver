<?php declare(strict_types=1);

namespace Elastic\ScoutDriver\Tests\Integration\Engine;

use Elastic\Adapter\Documents\DocumentManager;
use Elastic\Adapter\Indices\IndexManager;
use Elastic\Adapter\Search\Hit;
use Elastic\Adapter\Search\SearchParameters;
use Elastic\ScoutDriver\Engine;
use Elastic\ScoutDriver\Factories\DocumentFactoryInterface;
use Elastic\ScoutDriver\Factories\ModelFactoryInterface;
use Elastic\ScoutDriver\Factories\SearchParametersFactoryInterface;
use Elastic\ScoutDriver\Tests\App\Client;
use Elastic\ScoutDriver\Tests\Integration\TestCase;
use Illuminate\Database\Eloquent\Model;
use stdClass;

/**
 * @covers \Elastic\ScoutDriver\Engine
 *
 * @uses   \Elastic\ScoutDriver\Factories\DocumentFactory
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
            resolve(SearchParametersFactoryInterface::class),
            resolve(ModelFactoryInterface::class),
            resolve(IndexManager::class)
        );

        $engine->delete((new Client())->newCollection());
    }

    public function test_not_empty_model_collection_can_be_deleted_from_index(): void
    {
        $source = factory(Client::class, rand(6, 10))->create();

        $deleted = $source->slice(0, rand(2, 4))->each(static function (Model $client) {
            $client->forceDelete();
        });

        $searchParameters = (new SearchParameters())->query(['match_all' => new stdClass()]);
        $searchResult = $this->documentManager->search($source->first()->searchableAs(), $searchParameters);

        // assert that index has fewer documents
        $this->assertSame(
            $source->count() - $deleted->count(),
            $searchResult->total()
        );

        // assert that index doesn't have documents with ids corresponding to the deleted models
        $documentIds = $searchResult->hits()->map(static function (Hit $hit) {
            return $hit->document()->id();
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

        $searchParameters = (new SearchParameters())->query(['match_all' => new stdClass()]);
        $searchResult = $this->documentManager->search($clients->first()->searchableAs(), $searchParameters);

        // assert that index is empty
        $this->assertSame(0, $searchResult->total());
    }

    public function test_models_can_be_soft_deleted_from_index(): void
    {
        // enable soft deletes
        $this->config->set('scout.soft_delete', true);

        $clients = factory(Client::class, rand(2, 10))->create();

        $clients->each(static function (Model $client) {
            $client->delete();
        });

        $searchParameters = (new SearchParameters())->query(['match_all' => new stdClass()]);
        $searchResult = $this->documentManager->search($clients->first()->searchableAs(), $searchParameters);

        $searchResult->hits()->each(function (Hit $hit) {
            $this->assertSame(1, $hit->document()->content('__soft_deleted'));
        });
    }
}
