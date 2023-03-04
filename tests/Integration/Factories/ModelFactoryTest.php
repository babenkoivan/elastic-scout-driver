<?php declare(strict_types=1);

namespace Elastic\ScoutDriver\Tests\Integration\Factories;

use Elastic\Adapter\Search\SearchResult;
use Elastic\ScoutDriver\Factories\ModelFactory;
use Elastic\ScoutDriver\Tests\App\Client;
use Elastic\ScoutDriver\Tests\Integration\TestCase;
use Laravel\Scout\Builder;
use Laravel\Scout\Searchable;

/**
 * @covers \Elastic\ScoutDriver\Factories\ModelFactory
 *
 * @uses   \Elastic\ScoutDriver\Engine
 * @uses   \Elastic\ScoutDriver\Factories\DocumentFactory
 */
final class ModelFactoryTest extends TestCase
{
    private ModelFactory $modelFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelFactory = new ModelFactory();
    }

    public function factoryMethodProvider(): array
    {
        $methods = [['makeFromSearchResult']];

        // this method doesn't exist in Scout below v9
        if (method_exists(Searchable::class, 'queryScoutModelsByIds')) {
            $methods[] = ['makeLazyFromSearchResult'];
        }

        return $methods;
    }

    /**
     * @dataProvider factoryMethodProvider
     *
     * @testdox Test empty model collection is made from empty search response using $factoryMethod
     */
    public function test_empty_model_collection_is_made_from_empty_search_result(string $factoryMethod): void
    {
        $builder = new Builder(new Client(), 'test');

        $searchResult = new SearchResult([
            'hits' => [
                'total' => ['value' => 0],
                'hits' => [],
            ],
        ]);

        $models = $this->modelFactory->$factoryMethod($searchResult, $builder);

        $this->assertTrue($models->isEmpty());
    }

    /**
     * @dataProvider factoryMethodProvider
     *
     * @testdox Test empty model collection can be made from not empty search response using $factoryMethod
     */
    public function test_model_collection_can_be_made_from_not_empty_search_result(string $factoryMethod): void
    {
        $source = collect([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Martin'],
        ])->map(static fn (array $fields) => factory(Client::class)->create($fields));

        $builder = new Builder(new Client(), 'test');

        $searchResult = new SearchResult([
            'hits' => [
                'total' => ['value' => 3],
                'hits' => [
                    ['_id' => '3', ['_source' => ['name' => 'Bruce']]],
                    ['_id' => '2', ['_source' => ['name' => 'Martin']]],
                    ['_id' => '1', ['_source' => ['name' => 'John']]],
                ],
            ],
        ]);

        $models = $this->modelFactory->$factoryMethod($searchResult, $builder);

        $this->assertCount($source->count(), $models);
        $this->assertEquals($source->last()->toArray(), $models->first()->toArray());
        $this->assertEquals($source->first()->toArray(), $models->last()->toArray());
    }
}
