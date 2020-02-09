<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Engine;

use ElasticAdapter\Search\SearchResponse;
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
        // add some mixins
        factory(Client::class, rand(2, 10))->create();

        $target = factory(Client::class)->create(['name' => 'test']);
        $found = Client::search($target->name)->get();

        $this->assertCount(1, $found);
        $this->assertEquals($target->toArray(), $found->first()->toArray());
    }

    public function test_search_result_can_be_filtered(): void
    {
        // add some mixins
        factory(Client::class, rand(2, 10))->create();

        $target = factory(Client::class)->create(['phone_number' => '+1234567890']);
        $found = Client::search()->where('phone_number', $target->phone_number)->get();

        $this->assertCount(1, $found);
        $this->assertEquals($target->toArray(), $found->first()->toArray());
    }

    public function test_search_result_can_be_sorted(): void
    {
        $source = factory(Client::class, rand(2, 10))->create()->sortBy('email')->values();
        $found = Client::search()->orderBy('email', 'asc')->get();

        $this->assertEquals($source->toArray(), $found->toArray());
    }

    public function test_search_result_can_be_limited(): void
    {
        factory(Client::class, rand(10, 20))->create();

        $found = Client::search()->take(5)->get();

        $this->assertCount(5, $found);
    }

    public function test_search_result_can_be_paginated(): void
    {
        // add some mixins
        factory(Client::class, 6)->create();

        $target = factory(Client::class, 5)->create(['name' => 'John'])->sortBy('phone_number')->values();
        $paginator = Client::search($target->first()->name)->orderBy('phone_number', 'asc')->paginate(2, 'p', 3);

        $this->assertSame(2, $paginator->perPage());
        $this->assertSame('p', $paginator->getPageName());
        $this->assertSame(3, $paginator->currentPage());
        $this->assertSame(5, $paginator->total());
        $this->assertCount(1, $paginator->items());
        $this->assertEquals($target->last()->toArray(), $paginator[0]->toArray());
    }

    public function test_raw_search_returns_instance_of_search_response(): void
    {
        $source = factory(Client::class, rand(2, 10))->create();
        $foundRaw = Client::search()->raw();

        $this->assertInstanceOf(SearchResponse::class, $foundRaw);
        $this->assertSame($source->count(), $foundRaw->getHitsTotal());
    }
}
