<?php

namespace Queryable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Queryable\Traits\QueryParamFilterable;

class Group extends Model
{
    use QueryParamFilterable;

    protected $guarded = ['id'];

    public function users()
    {
        return $this->hasMany(\Queryable\Tests\Models\User::class);
    }

    public function creator()
    {
        return $this->hasOne(\Queryable\Tests\Models\User::class);
    }
}
