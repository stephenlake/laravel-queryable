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
        if (!method_exists($model, 'getQueryables')) {
            $class = get_class($model);

            throw new \Exception("The required 'getQueryables' function is not defined on the '{$class}' model.");
        }

        $this->queryables = $model->getQueryables();

        if ($term = request($this->queryableConfig['searchKeyName'] ?? 'search', false)) {
            $this->parseQueryParamSearchables($builder, $term);
        }

        if (request($this->queryableConfig['filterKeyName'] ?? 'filter', false) == 'on') {
            $this->parseQueryParamFilterables($builder);
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

                if (in_array($column, $this->queryables)) {
                    $this->parseFilter($query, $column, $operator, $values);
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
    private function parseFilter($query, $column, $operator, $value)
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
                  $operator = 'ilike';
                  $value = str_replace('*', '%', $value);
              }
              $this->queryParamFilterQueryConstruct($query, $column, $value, 'where', $operator);
              break;

          case '!=~':
              $value = explode(',', $value);
              $this->queryParamFilterQueryConstruct($query, $column, $value, 'whereNotIn');
              break;

          case '=~':
              $value = explode(',', $value);
              $this->queryParamFilterQueryConstruct($query, $column, $value, 'whereIn');
              break;

          case '||=':
              $this->queryParamFilterQueryConstruct($query, $column, $value, 'orWhere', 'ilike');
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

    /**
     * Parses the provided searchables.
     *
     * @return void
     */
    private function parseQueryParamSearchables($query, $term)
    {
        if (count($this->queryables)) {
            $query->where(function ($subquery) use ($term) {
                foreach ($this->queryables as $queryable => $value) {
                    $this->queryParamFilterQueryConstruct($subquery, $value, "%{$term}%", 'orWhere', 'ilike');
                }
            });
        }
    }
}
