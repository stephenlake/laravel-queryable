<?php

namespace Queryable\Traits;

trait QueryParamFilterable
{
    /**
     * Queryable attributes.
     *
     * @var array
     */
    private $queryables = [];

    /**
     * Database driver name.
     *
     * @var string
     */
    private $databaseDriver;

    /**
     * Query scope to apply filters..
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array                                 $filterable
     * @param array|null                            $filters
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFilters($query, $filterable, $filters = null)
    {
        $this->databaseDriver = $this->getConnection()->getDriverName();
        $this->queryables = $filterable;

        if (count($this->queryables)) {
            $this->parseQueryParamFilterables($query, $filters);
        }

        return $query;
    }

    /**
     * Parse potential query paramters.
     *
     * @return void
     */
    private function parseQueryParamFilterables($query, $filters = null)
    {
        $filters = $filters ?? explode('&', str_replace('->', '.', urldecode(request()->getQueryString())));

        if (count($filters) > 1) {
            if (starts_with($filters[0], '!')) {
                $filters[0] = substr($filters[0], 1);
            }
        }

        foreach ($filters as $rawFilter) {
            if (str_contains($rawFilter, '!=~')) {
                $operator = '!=~';
            } elseif (str_contains($rawFilter, '=~')) {
                $operator = '=~';
            } elseif (str_contains($rawFilter, '>=')) {
                $operator = '>=';
            } elseif (str_contains($rawFilter, '<=')) {
                $operator = '<=';
            } elseif (str_contains($rawFilter, '!=')) {
                $operator = '!=';
            } elseif (str_contains($rawFilter, '=')) {
                $operator = '=';
            } elseif (str_contains($rawFilter, '>')) {
                $operator = '>';
            } elseif (str_contains($rawFilter, '<')) {
                $operator = '<';
            } else {
                break;
            }

            $params = explode($operator, $rawFilter);

            if (count($params) == 2) {
                $column = $params[0];
                $values = $params[1];

                if ($isOr = starts_with($column, '!')) {
                    $column = substr($column, 1);
                }

                if (in_array($column, $this->queryables)) {
                    $this->parseFilter($query, $column, $operator, $values, $isOr);
                }
            }
        }

        if (($orderBy = request()->query('orderBy'))) {
            $value = explode(',', $orderBy);
            $query->orderBy($value[0], $value[1] ?? 'asc');
        }
    }

    /**
     * Parse filter query paramters.
     *
     * @return void
     */
    private function parseFilter($query, $column, $operator, $value, $isOr = false)
    {
        $value = $value == 'NULL' ? null : $value;

        if (in_array($operator, ['=', '!=', '>', '<', '>=', '<='], true)) {
            if (ends_with($value, '*') || starts_with($value, '*')) {
                if (starts_with($operator, '!')) {
                    $operator = $this->databaseDriver == 'pgsql' ? 'NOT ILIKE' : 'NOT LIKE';
                } else {
                    $operator = $this->databaseDriver == 'pgsql' ? 'ILIKE' : 'LIKE';
                }
                $value = str_replace('*', '%', $value);
            }
            $compare = $isOr ? 'orWhere' : 'where';
        } elseif ($operator == '!=~') {
            $value = explode(',', $value);
            $compare = $isOr ? 'orWhereNotIn' : 'whereNotIn';
            $operator = false;
        } elseif ($operator == '=~') {
            $value = explode(',', $value);
            $compare = $isOr ? 'orWhereIn' : 'whereIn';
            $operator = false;
        }

        $this->queryParamFilterQueryConstruct($query, $column, $value, $compare, $operator);
    }

    /**
     * Append queries to query builder.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function queryParamFilterQueryConstruct($query, $column, $value, $operation, $operator = false)
    {
        if (str_contains($column, '.')) {
            $keys = explode('.', $column);
            $attribute = $keys[count($keys) - 1];
            $relations = str_replace(".{$attribute}", '', implode('.', $keys));

            if ($operation == 'orWhere') {
                $parentOperation = 'orWhereHas';
            } else {
                $parentOperation = 'whereHas';
            }

            $column = $attribute;

            $query->$parentOperation($relations, function ($subquery) use ($column, $operation, $operator, $value) {
                return $this->appendQuery($subquery, $operation, $column, $operator, $value);
            });
        } else {
            return $this->appendQuery($query, $operation, $column, $operator, $value);
        }
    }

    /**
     * Append queries to query builder.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function appendQuery($query, $operation, $column, $operator, $value)
    {
        $operation = "{$operation}Raw";

        if (!$operator) {
            $operator = '=';
        }

        return $query->$operation("LOWER($column) $operator ?", [strtolower($value)]);
    }

    /**
     * Get the models database connection.
     *
     * @return \Illuminate\Database\Connection
     */
    abstract public function getConnection();
}
