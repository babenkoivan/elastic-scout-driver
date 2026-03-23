<?php declare(strict_types=1);

namespace Elastic\ScoutDriver\Tests\Integration\Factories;

use Elastic\ScoutDriver\Factories\SearchParametersFactory;
use Elastic\ScoutDriver\Tests\App\Client;
use Elastic\ScoutDriver\Tests\Integration\TestCase;
use Laravel\Scout\Builder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

#[CoversClass(SearchParametersFactory::class)]
final class SearchParametersFactoryTest extends TestCase
{
    private SearchParametersFactory $searchParametersFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchParametersFactory = new SearchParametersFactory();
    }

    public function test_search_parameters_can_be_made_from_builder_with_empty_query_string(): void
    {
        $model = new Client();
        $builder = new Builder($model, '');
        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder);

        $this->assertEquals([
            'index' => $model->searchableAs(),
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'match_all' => new stdClass(),
                        ],
                    ],
                ],
            ],
        ], $searchParameters->toArray());
    }

    public function test_search_parameters_can_be_made_from_builder_with_not_empty_query_string(): void
    {
        $model = new Client();
        $builder = new Builder($model, 'foo');
        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder);

        $this->assertSame([
            'index' => $model->searchableAs(),
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'query_string' => ['query' => 'foo'],
                        ],
                    ],
                ],
            ],
        ], $searchParameters->toArray());
    }

    public static function whereFilterProvider(): array
    {
        return [
            'equal' => [
                '=', 60,
                ['term' => ['price' => 60]],
            ],
            'not equal' => [
                '!=', 60,
                [
                    'bool' => [
                        'must_not' => [
                            ['term' => ['price' => 60]],
                        ],
                    ],
                ],
            ],
            'greater than' => [
                '>', 60,
                ['range' => ['price' => ['gt' => 60]]],
            ],
            'greater or equal' => [
                '>=', 60,
                ['range' => ['price' => ['gte' => 60]]],
            ],
            'less than' => [
                '<', 60,
                ['range' => ['price' => ['lt' => 60]]],
            ],
            'less or equal' => [
                '<=', 60,
                ['range' => ['price' => ['lte' => 60]]],
            ],
        ];
    }

    #[DataProvider('whereFilterProvider')]
    public function test_search_parameters_can_be_made_from_builder_with_where_filter(string $operator, mixed $value, array $expectedFilter): void
    {
        $model = new Client();
        $builder = (new Builder($model, 'book'))->where('price', $operator, $value);
        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder);

        $this->assertSame([
            'index' => $model->searchableAs(),
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'query_string' => ['query' => 'book'],
                        ],
                        'filter' => [
                            $expectedFilter,
                        ],
                    ],
                ],
            ],
        ], $searchParameters->toArray());
    }

    public function test_search_parameters_can_be_made_from_builder_with_where_in_filter(): void
    {
        $model = new Client();
        $builder = (new Builder($model, 'book'))->whereIn('author_id', [1, 2]);
        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder);

        $this->assertSame([
            'index' => $model->searchableAs(),
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'query_string' => ['query' => 'book'],
                        ],
                        'filter' => [
                            [
                                'bool' => [
                                    'must' => [
                                        ['terms' => ['author_id' => [1, 2]]],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $searchParameters->toArray());
    }

    public function test_search_parameters_can_be_made_from_builder_with_where_not_in_filter(): void
    {
        $model = new Client();
        $builder = (new Builder($model, 'book'))->whereNotIn('author_id', [1, 2]);
        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder);

        $this->assertSame([
            'index' => $model->searchableAs(),
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'query_string' => ['query' => 'book'],
                        ],
                        'filter' => [
                            [
                                'bool' => [
                                    'must_not' => [
                                        ['terms' => ['author_id' => [1, 2]]],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $searchParameters->toArray());
    }

    public function test_search_parameters_can_be_made_from_builder_with_sort(): void
    {
        $model = new Client();
        $builder = new Builder($model, 'book');
        $builder->orderBy('price');
        $builder->orderBy('author_id', 'desc');

        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder);

        $this->assertSame([
            'index' => $model->searchableAs(),
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'query_string' => ['query' => 'book'],
                        ],
                    ],
                ],
                'sort' => [
                    ['price' => 'asc'],
                    ['author_id' => 'desc'],
                ],
            ],
        ], $searchParameters->toArray());
    }

    public function test_search_parameters_can_be_made_from_builder_with_limit(): void
    {
        $model = new Client();
        $builder = new Builder($model, 'book');
        $builder->take(10);

        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder);

        $this->assertSame([
            'index' => $model->searchableAs(),
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'query_string' => ['query' => 'book'],
                        ],
                    ],
                ],
                'size' => 10,
            ],
        ], $searchParameters->toArray());
    }

    public function test_search_parameters_can_be_made_from_builder_with_pagination(): void
    {
        $model = new Client();
        $builder = new Builder($model, 'book');
        $builder->take(10);

        $searchParameters = $this->searchParametersFactory->makeFromBuilder($builder, ['page' => 3, 'perPage' => 30]);

        $this->assertSame([
            'index' => $model->searchableAs(),
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'query_string' => ['query' => 'book'],
                        ],
                    ],
                ],
                'from' => 60,
                'size' => 30,
            ],
        ], $searchParameters->toArray());
    }
}
