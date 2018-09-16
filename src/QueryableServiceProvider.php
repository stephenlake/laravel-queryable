<?php

namespace Queryable;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class QueryableServiceProvider extends LaravelServiceProvider
{
    /**
     * Boot up Queryable.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/queryable.php' => config_path('queryable.php'),
        ]);
    }
}
