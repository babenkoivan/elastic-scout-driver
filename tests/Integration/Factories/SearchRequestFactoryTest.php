<?php declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Factories;

use ElasticScoutDriver\Factories\SearchRequestFactory;
use ElasticScoutDriver\Tests\App\Client;
use ElasticScoutDriver\Tests\Integration\TestCase;
use Laravel\Scout\Builder;
use stdClass;

/**
 * @covers \ElasticScoutDriver\Factories\SearchRequestFactory
 */
final class SearchRequestFactoryTest extends TestCase
{
    /**
     * @var SearchRequestFactory
     */
    private $searchRequestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchRequestFactory = new SearchRequestFactory();
    }

    public function test_search_request_can_be_made_from_builder_with_empty_query_string(): void
    {
        $builder = new Builder(new Client(), '');
        $searchRequest = $this->searchRequestFactory->makeFromBuilder($builder);

        $this->assertEquals([
            'query' => [
                'bool' => [
                    'must' => [
                        'match_all' => new stdClass(),
                    ],
                ],
            ],
        ], $searchRequest->toArray());
    }

    public function test_search_request_can_be_made_from_builder_with_not_empty_query_string(): void
    {
        $builder = new Builder(new Client(), 'foo');
        $searchRequest = $this->searchRequestFactory->makeFromBuilder($builder);

        $this->assertSame([
            'query' => [
                'bool' => [
                    'must' => [
                        'query_string' => ['query' => 'foo'],
                    ],
                ],
            ],
        ], $searchRequest->toArray());
    }

    public function test_search_request_can_be_made_from_builder_with_filters(): void
    {
        $builder = new Builder(new Client(), 'book');
        $builder->where('author_id', 1);
        $builder->where('price', 60);

        $searchRequest = $this->searchRequestFactory->makeFromBuilder($builder);

        $this->assertSame([
            'query' => [
                'bool' => [
                    'must' => [
                        'query_string' => ['query' => 'book'],
                    ],
                    'filter' => [
                        ['term' => ['author_id' => 1]],
                        ['term' => ['price' => 60]],
                    ],
                ],
            ],
        ], $searchRequest->toArray());
    }

    public function test_search_request_can_be_made_from_builder_with_sort(): void
    {
        $builder = new Builder(new Client(), 'book');
        $builder->orderBy('price', 'asc');
        $builder->orderBy('author_id', 'desc');

        $searchRequest = $this->searchRequestFactory->makeFromBuilder($builder);

        $this->assertSame([
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
        ], $searchRequest->toArray());
    }

    public function test_search_request_can_be_made_from_builder_with_limit(): void
    {
        $builder = new Builder(new Client(), 'book');
        $builder->take(10);

        $searchRequest = $this->searchRequestFactory->makeFromBuilder($builder);

        $this->assertSame([
            'query' => [
                'bool' => [
                    'must' => [
                        'query_string' => ['query' => 'book'],
                    ],
                ],
            ],
            'size' => 10,
        ], $searchRequest->toArray());
    }

    public function test_search_request_can_be_made_from_builder_with_pagination(): void
    {
        $builder = new Builder(new Client(), 'book');
        $builder->take(10);

        $searchRequest = $this->searchRequestFactory->makeFromBuilder($builder, ['page' => 3, 'perPage' => 30]);

        $this->assertSame([
            'query' => [
                'bool' => [
                    'must' => [
                        'query_string' => ['query' => 'book'],
                    ],
                ],
            ],
            'from' => 60,
            'size' => 30,
        ], $searchRequest->toArray());
    }
}
