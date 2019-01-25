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
     * Debugging.
     *
     * @var bool
     */
    private $debugging;

    /**
    * Query scope to apply filters..
    *
    * @param \Illuminate\Database\Eloquent\Builder $query
    * @param array $filterable
    * @param array|null $filters
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

        $this->debug($query->toSql());

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

        $this->debug(print_r($filters, true), false);

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
        $this->debug(print_r(compact('column', 'operator', 'value', 'isOr'), true));

        $value = $value == 'NULL' ? null : $value;

        switch ($operator) {
          case '=':
          case '!=':
          case '>':
          case '<':
          case '>=':
          case '<=':
              if (ends_with($value, '*') || starts_with($value, '*')) {
                  if (starts_with($operator, '!')) {
                      $operator = $this->databaseDriver == 'pgsql' ? 'NOT ILIKE' : 'NOT LIKE';
                  } else {
                      $operator = $this->databaseDriver == 'pgsql' ? 'ILIKE' : 'LIKE';
                  }
                  $value = str_replace('*', '%', $value);
              }
              $this->queryParamFilterQueryConstruct($query, $column, $value, $isOr ? 'orWhere' : 'where', $operator);
              break;

          case '!=~':
              $value = explode(',', $value);
              $this->queryParamFilterQueryConstruct($query, $column, $value, $isOr ? 'orWhereNotIn' : 'whereNotIn');
              break;

          case '=~':
              $value = explode(',', $value);
              $this->queryParamFilterQueryConstruct($query, $column, $value, $isOr ? 'orWhereIn' : 'whereIn');
              break;
        }
    }

    /**
     * Append queries to query builder.
     *
     * @return void
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
     * Debug queries.
     *
     * @return void
     */
    private function debug($output, $append = true)
    {
        if ($this->debugging === true) {
            if ($append) {
                file_put_contents(base_path('queryables.txt'), $output.PHP_EOL.PHP_EOL, FILE_APPEND);
            } else {
                file_put_contents(base_path('queryables.txt'), $output);
            }
        }
    }

    /**
     * Get the models database connection.
     *
     * @return Illuminate\Database\Connection;
     */
    abstract public function getConnection();
}
