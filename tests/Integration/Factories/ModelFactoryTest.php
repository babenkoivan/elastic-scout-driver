<?php declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Factories;

use ElasticAdapter\Search\SearchResponse;
use ElasticScoutDriver\Factories\ModelFactory;
use ElasticScoutDriver\Tests\App\Client;
use ElasticScoutDriver\Tests\Integration\TestCase;
use Laravel\Scout\Builder;

/**
 * @covers \ElasticScoutDriver\Factories\ModelFactory
 *
 * @uses   \ElasticScoutDriver\Engine
 * @uses   \ElasticScoutDriver\Factories\DocumentFactory
 */
final class ModelFactoryTest extends TestCase
{
    /**
     * @var ModelFactory
     */
    private $modelFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelFactory = new ModelFactory();
    }

    public function test_empty_model_collection_is_made_from_empty_search_response(): void
    {
        $builder = new Builder(new Client(), 'test');

        $searchResponse = new SearchResponse([
            'hits' => [
                'total' => ['value' => 0],
                'hits' => [],
            ],
        ]);

        $models = $this->modelFactory->makeFromSearchResponseUsingBuilder($searchResponse, $builder);

        $this->assertTrue($models->isEmpty());
    }

    public function test_model_collection_can_be_made_from_not_empty_search_response(): void
    {
        $clients = collect([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Martin'],
        ])->map(static function (array $fields) {
            return factory(Client::class)->create($fields);
        });

        $builder = new Builder(new Client(), 'test');

        $searchResponse = new SearchResponse([
            'hits' => [
                'total' => ['value' => 3],
                'hits' => [
                    ['_id' => '3', ['_source' => ['name' => 'Bruce']]],
                    ['_id' => '2', ['_source' => ['name' => 'Martin']]],
                    ['_id' => '1', ['_source' => ['name' => 'John']]],
                ],
            ],
        ]);

        $models = $this->modelFactory->makeFromSearchResponseUsingBuilder($searchResponse, $builder);

        $this->assertCount($clients->count(), $models);
        $this->assertEquals($clients->last()->toArray(), $models->first()->toArray());
        $this->assertEquals($clients->first()->toArray(), $models->last()->toArray());
    }
}
