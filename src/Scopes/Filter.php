<?php

namespace Queryable\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Filter implements Scope
{
    /**
     * Queryable configuration.
     *
     * @var array
     */
    private $queryableConfig;

    /**
     * Queryable regex operator pattern.
     *
     * @var string
     */
    private $queryableOperatorPattern = '/([!=|<=|<|>=|>|=|!=~|=~])/m';

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
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (!method_exists($model, 'getQueryables')) {
            $class = get_class($model);

            throw new \Exception("The required 'getQueryables' function is not defined on the '{$class}' model. Read the docs at https://stephenlake.github.io/laravel-queryables");
        }

        $this->queryables = array_merge($model->getQueryables());

        if ($term = request($this->queryableConfig['searchKeyName'] ?? 'search', false)) {
            $this->parseQueryParamSearchables($builder, $term);
        }

        if (request($this->queryableConfig['filterKeyName'] ?? 'filter', false)) {
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
        $filters = explode('&', urldecode(request()->getQueryString()));

        foreach ($filters as $rawFilter) {
            $filter = preg_split($this->queryableOperatorPattern, $rawFilter, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            $filterParamCount = count($filter);

            if ($filterParamCount >= 3 && $this->queryParamFilterAllowed($filter[0])) {
                $query->where(function ($query) use ($filter, $filterParamCount) {
                    $this->parseFilter($query, $filter, $filterParamCount);
                });
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
    private function parseFilter($query, $filter, $filterParamCount)
    {
        $column = $filter[0];

        if ($filterParamCount == 4) {
            $operator = "{$filter[1]}{$filter[2]}";
            $value = $filter[3];
        } elseif ($filterParamCount == 5) {
            $operator = "{$filter[1]}{$filter[2]}{$filter[3]}";
            $value = $filter[4];
        } elseif ($filterParamCount == 3) {
            $operator = $filter[1];
            $value = $filter[2];
        }

        $value = $value == 'NULL' ? null : $value;

        switch ($operator) {
          case '=':
          case '!=':
          case '>':
          case '<':
          case '>=':
          case '<=':
              if (ends_with($value, '*') || starts_with($value, '*')) {
                  $operator = 'like';
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
            $attribute = $keys[count($keys)-1];
            $relations = str_replace(".{$attribute}", '', implode('.', $keys));

            $query->whereHas($relations, function ($query) use ($attribute, $operation, $operator, $value) {
                if ($operator) {
                    $query->$operation($attribute, $operator, $value);
                } else {
                    $query->$operation($attribute, $value);
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
     * Returns true if the parameter is allowed to be filtered against.
     *
     * @return boolean
     */
    private function queryParamFilterAllowed($key)
    {
        return in_array($key, $this->queryables);
    }

    /**
     * Parses the provided searchables
     *
     * @return void
     */
    private function parseQueryParamSearchables($query, $term)
    {
        foreach ($this->queryables as $queryable => $value) {
            if ($this->isQueryParamRaw($value)) {
                $this->appendQueryParamRaw($query, $value, $term);
            } elseif ($this->isQueryParamRelationAttribute($value)) {
                $this->appendQueryParamRelationAttribute($query, $value, $term);
            } else {
                $this->appendQueryParamComparison($query, $value, $term);
            }
        }
    }

    /**
     * Returns true if the query value is a raw SQL query.
     *
     * @return boolean
     */
    private function isQueryParamRaw($value)
    {
        return starts_with($value, 'raw::');
    }

    /**
     * Returns true if the query column is a relationship.
     *
     * @return boolean
     */
    private function isQueryParamRelationAttribute($value)
    {
        return str_contains($value, '.');
    }

    /**
     * Append queries to quiery builder.
     *
     * @return void
     */
    private function appendQueryParamRaw($query, $value, $term)
    {
        $raw = substr($value, 5);

        $query->orWhere(DB::raw($raw), 'ilike', "%{$term}%");
    }

    /**
     * Append queries to quiery builder.
     *
     * @return void
     */
    private function appendQueryParamRelationAttribute($query, $value, $term)
    {
        $keys = explode('.', $value);
        $attribute = $keys[count($keys)-1];
        $relations = str_replace(".{$attribute}", '', implode('.', $keys));

        $query->orWhereHas($relations, function ($query) use ($attribute, $term) {
            $this->appendQueryParamComparison($query, $attribute, $term);
        });
    }

    /**
     * Append queries to quiery builder.
     *
     * @return void
     */
    private function appendQueryParamComparison($query, $value, $term)
    {
        $term = str_replace('*', '%', $term);

        $query->orWhere($value, 'ilike', $term);
    }
}
