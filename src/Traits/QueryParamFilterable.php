<?php
namespace Queryable\Traits;

trait QueryParamFilterable
{
    /**
     * Queryable configuration.
     *
     * @var array
     */
    private $queryableConfig;

    /**
     * Queryable attributes.
     *
     * @var array
     */
    private $queryables = [];

    /**
     * Database driver name
     *
     * @var string
     */
    private $databaseDriver;

    public function scopeWithFilters($query, ...$filterable)
    {
        $this->queryableConfig = config('queryable');
        $this->databaseDriver = $this->getConnection()->getDriverName();
        $this->queryables = is_array($filterable) ? $filterable : $filterable;

        if (count($this->queryables)) {
            if (request($this->queryableConfig['filterKeyName'] ?? 'filters', false) == 'on') {
                $this->parseQueryParamFilterables($query);
            }
        }
    }

    /**
     * Parse potential query paramters.
     *
     * @return void
     */
    private function parseQueryParamFilterables($query)
    {
        $filters = explode('&', str_replace('->', '.', urldecode(request()->getQueryString())));

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

        if ($orderBy = request()->query('orderBy', false)) {
            $value = explode(',', $orderBy);
            $query->orderBy($value[0], $value[1] ?? ($this->queryableConfig['defaultOrdering'] ?? 'asc'));
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

        switch ($operator) {
          case '=':
          case '!=':
          case '>':
          case '<':
          case '>=':
          case '<=':
              if (ends_with($value, '*') || starts_with($value, '*')) {
                  $operator =  $this->databaseDriver == 'pgsql' ? 'ilike' : 'like';
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

            $query->$parentOperation($relations, function ($subquery) use ($column, $operation, $operator, $value) {
                $this->appendQuery($subquery, $operation, $column, $operator, $value);
            });
        } else {
            $this->appendQuery($query, $operation, $column, $operator, $value);
        }
    }

    private function appendQuery($query, $operation, $column, $operator, $value)
    {
        if ($operator) {
            $query->$operation($column, $operator, $value);
        } else {
            $query->$operation($column, $value);
        }
    }
}