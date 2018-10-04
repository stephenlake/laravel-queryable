<?php

namespace Queryable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Queryable\Traits\QueryParamFilterable;

class User extends Model
{
    use QueryParamFilterable;

    protected $guarded = ['id'];

    public function group()
    {
        return $this->belongsTo(\Queryable\Tests\Models\Group::class);
    }
}
