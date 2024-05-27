<?php declare(strict_types=1);

namespace Elastic\ScoutDriver\Tests\Integration\Factories;

use Elastic\Adapter\Search\SearchResult;
use Elastic\ScoutDriver\Engine;
use Elastic\ScoutDriver\Factories\DocumentFactory;
use Elastic\ScoutDriver\Factories\ModelFactory;
use Elastic\ScoutDriver\Tests\App\Client;
use Elastic\ScoutDriver\Tests\Integration\TestCase;
use Laravel\Scout\Builder;
use Laravel\Scout\Searchable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ModelFactory::class)]
#[UsesClass(Engine::class)]
#[UsesClass(DocumentFactory::class)]
final class ModelFactoryTest extends TestCase
{
    private ModelFactory $modelFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelFactory = new ModelFactory();
    }

    public static function factoryMethodProvider(): array
    {
        return [
            ['makeFromSearchResult'],
            ['makeLazyFromSearchResult'],
        ];
    }

    #[DataProvider('factoryMethodProvider')]
    #[TestDox('Test empty model collection is made from empty search response using $factoryMethod')]
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

    #[DataProvider('factoryMethodProvider')]
    #[TestDox('Test empty model collection can be made from not empty search response using $factoryMethod')]
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
