<?php declare(strict_types=1);

namespace Elastic\ScoutDriver\Factories;

use Elastic\Adapter\Search\SearchParameters;
use Laravel\Scout\Builder;
use stdClass;

class SearchParametersFactory implements SearchParametersFactoryInterface
{
    public function makeFromBuilder(Builder $builder, array $options = []): SearchParameters
    {
        $searchParameters = new SearchParameters();

        $index = $this->makeIndex($builder);
        $searchParameters->indices([$index]);

        $query = $this->makeQuery($builder);
        $searchParameters->query($query);

        if ($sort = $this->makeSort($builder)) {
            $searchParameters->sort($sort);
        }

        if ($from = $this->makeFrom($options)) {
            $searchParameters->from($from);
        }

        if ($size = $this->makeSize($builder, $options)) {
            $searchParameters->size($size);
        }

        return $searchParameters;
    }

    protected function makeIndex(Builder $builder): string
    {
        return $builder->index ?: $builder->model->searchableAs();
    }

    protected function makeQuery(Builder $builder): array
    {
        $query = [
            'bool' => [],
        ];

        if (!empty($builder->query)) {
            $query['bool']['must'] = [
                'query_string' => [
                    'query' => $builder->query,
                ],
            ];
        } else {
            $query['bool']['must'] = [
                'match_all' => new stdClass(),
            ];
        }

        if ($filter = $this->makeFilter($builder)) {
            $query['bool']['filter'] = $filter;
        }

        return $query;
    }

    protected function makeFilter(Builder $builder): ?array
    {
        $filter = collect($builder->wheres)->map(function ($where) {
            $field = $where['field'];
            $operator = $where['operator'];
            $value = $where['value'];

            return $this->makeWhereClause($field, $operator, $value);
        })->values();

        $whereIns = collect($builder->whereIns)->map(static fn (array $values, string $field) => [
            'terms' => [$field => $values],
        ])->values();

        if ($whereIns->isNotEmpty()) {
            $filter->push([
                'bool' => [
                    'must' => $whereIns->all(),
                ],
            ]);
        }

        $whereNotIns = collect($builder->whereNotIns)->map(static fn (array $values, string $field) => [
            'terms' => [$field => $values],
        ])->values();

        if ($whereNotIns->isNotEmpty()) {
            $filter->push([
                'bool' => [
                    'must_not' => $whereNotIns->all(),
                ],
            ]);
        }

        return $filter->isEmpty() ? null : $filter->all();
    }

    protected function makeWhereClause(
        string $field,
        string $operator,
        mixed $value
    ): array {
        return match ($operator) {
            '=' => [
                'term' => [$field => $value],
            ],
            '!=' => [
                'bool' => [
                    'must_not' => [
                        ['term' => [$field => $value]],
                    ],
                ],
            ],
            '>' => [
                'range' => [
                    $field => ['gt' => $value],
                ],
            ],
            '>=' => [
                'range' => [
                    $field => ['gte' => $value],
                ],
            ],
            '<' => [
                'range' => [
                    $field => ['lt' => $value],
                ],
            ],
            '<=' => [
                'range' => [
                    $field => ['lte' => $value],
                ],
            ],
            default => throw new \InvalidArgumentException(
                "Unsupported operator [{$operator}] for field [{$field}]"
            ),
        };
    }

    protected function makeSort(Builder $builder): ?array
    {
        $sort = collect($builder->orders)->map(static fn (array $order) => [
            $order['column'] => $order['direction'],
        ]);

        return $sort->isEmpty() ? null : $sort->all();
    }

    protected function makeFrom(array $options): ?int
    {
        if (isset($options['page'], $options['perPage'])) {
            return ($options['page'] - 1) * $options['perPage'];
        }

        return null;
    }

    protected function makeSize(Builder $builder, array $options): ?int
    {
        return $options['perPage'] ?? $builder->limit;
    }
}
