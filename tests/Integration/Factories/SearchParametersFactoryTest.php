<?php declare(strict_types=1);

namespace Elastic\ScoutDriver\Tests\Integration\Factories;

use Elastic\ScoutDriver\Factories\SearchParametersFactory;
use Elastic\ScoutDriver\Tests\App\Client;
use Elastic\ScoutDriver\Tests\Integration\TestCase;
use Laravel\Scout\Builder;
use stdClass;

/**
 * @covers \Elastic\ScoutDriver\Factories\SearchParametersFactory
 */
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

    public function test_search_parameters_can_be_made_from_builder_with_where_filter(): void
    {
        $model = new Client();
        $builder = (new Builder($model, 'book'))->where('price', 60);
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
                            ['term' => ['price' => 60]],
                        ],
                    ],
                ],
            ],
        ], $searchParameters->toArray());
    }

    public function test_search_parameters_can_be_made_from_builder_with_where_in_filter(): void
    {
        if (!method_exists(Builder::class, 'whereIn')) {
            $this->markTestSkipped('Method "whereIn" is not supported by current Scout version');
        }

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
        if (!method_exists(Builder::class, 'whereNotIn')) {
            $this->markTestSkipped('Method "whereNotIn" is not supported by current Scout version');
        }

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
