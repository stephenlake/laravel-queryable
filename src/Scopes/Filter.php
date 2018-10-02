<?php

namespace Queryable\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class Filter implements Scope
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
    private $queryables;

    /**
     * Database driver name
     *
     * @var string
     */
    private $databaseDriver;

    /**
     * Construct!
     *
     * @return void
     */
    public function __construct()
    {
        if (!$this->queryableConfig) {
            $this->queryableConfig = config('queryable');
        }
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model   $model
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (method_exists($model, 'getQueryables')) {
            $this->queryables = $model->getQueryables();
            $this->databaseDriver = $model->getConnection()->getDriverName();
        }

        if (count($this->queryables)) {
            if (request($this->queryableConfig['filterKeyName'] ?? 'filter', false) == 'on') {
                $this->parseQueryParamFilterables($builder);
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

                if (in_array($column, $this->queryables)) {
                    $values = $params[1];

                    if ($isOr = starts_with($column, '!')) {
                        $column = substr($column, 1);
                    }
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
                if ($operator) {
                    $subquery->$operation($column, $operator, $value);
                } else {
                    $subquery->$operation($column, $value);
                }
            });
        } else {
            if ($operator) {
                $query->$operation($column, $operator, $value);
            } else {
                $query->$operation($column, $value);
            }
        }
    }
}
