<?php

namespace Queryable\Traits;

use Queryable\Scopes\Filter;

trait QueryParamFilterable
{
    /**
     * Boot QueryFaramFilterable
     *
     * @return void
     */
    protected static function bootQueryParamFilterable()
    {
        static::addGlobalScope(new Filter());
    }
}
