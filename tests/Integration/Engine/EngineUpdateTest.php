<?php declare(strict_types=1);

namespace Elastic\ScoutDriver\Tests\Integration\Engine;

use Elastic\Adapter\Documents\DocumentManager;
use Elastic\Adapter\Indices\IndexManager;
use Elastic\ScoutDriver\Engine;
use Elastic\ScoutDriver\Factories\DocumentFactoryInterface;
use Elastic\ScoutDriver\Factories\ModelFactoryInterface;
use Elastic\ScoutDriver\Factories\SearchParametersFactoryInterface;
use Elastic\ScoutDriver\Tests\App\Client;
use Elastic\ScoutDriver\Tests\Integration\TestCase;

/**
 * @covers \Elastic\ScoutDriver\Engine
 *
 * @uses   \Elastic\ScoutDriver\Factories\DocumentFactory
 * @uses   \Elastic\ScoutDriver\Factories\ModelFactory
 * @uses   \Elastic\ScoutDriver\Factories\SearchParametersFactory
 */
final class EngineUpdateTest extends TestCase
{
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
        $source = factory(Client::class, rand(2, 10))->create();
        $found = Client::search()->get();

        // assert that the amount of created models corresponds number of found models
        $this->assertSame($source->count(), $found->count());
        // assert that all source models are found
        $this->assertCount(0, $source->pluck('id')->diff($found->pluck('id')));
    }
}
