<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Engine;

use ElasticScoutDriver\Tests\app\Client;
use ElasticScoutDriver\Tests\Integration\TestCase;

/**
 * @covers \ElasticScoutDriver\Engine
 * @uses   \ElasticScoutDriver\Factories\DocumentFactory
 * @uses   \ElasticScoutDriver\Factories\ModelFactory
 * @uses   \ElasticScoutDriver\Factories\SearchRequestFactory
 */
final class EngineSearchTest extends TestCase
{
    public function test_ids_can_be_retrieved_from_search_result(): void
    {
        $source = factory(Client::class, rand(2, 10))->create();
        $found = Client::search()->keys();

        $this->assertEquals(
            $source->pluck($source->first()->getKeyName())->all(),
            $found->all()
        );
    }

    public function test_all_models_can_be_found(): void
    {
        $source = factory(Client::class, rand(2, 10))->create();
        $found = Client::search()->get();

        $this->assertEquals($source->toArray(), $found->toArray());
    }

    public function test_models_corresponding_query_string_can_be_found(): void
    {
        // add some fixtures
        factory(Client::class, rand(2, 10))->create();

        $target = factory(Client::class)->create(['name' => 'test']);
        $found = Client::search($target->name)->get();

        $this->assertCount(1, $found);
        $this->assertEquals($target->toArray(), $found->first()->toArray());
    }
}
