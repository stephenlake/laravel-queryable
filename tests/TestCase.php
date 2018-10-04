<?php

namespace Queryable\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Queryable\Tests\Models\Group;
use Queryable\Tests\Models\User;

abstract class TestCase extends BaseTestCase
{
    public $faker;

    public function setup()
    {
        parent::setup();

        $this->app->setBasePath(__DIR__.'/../');
        $this->faker = \Faker\Factory::create();
        $this->createShitData();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        Schema::dropIfExists('users');
        Schema::dropIfExists('groups');

        Schema::create('groups', function ($table) {
            $table->increments('id');
            $table->integer('creator_id')->nullable();
            $table->string('name');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->integer('group_id')->nullable();
            $table->string('firstname');
            $table->string('lastname');
            $table->timestamps();
        });
    }

    public function createShitData()
    {
        for ($i = 0; $i < 300; $i++) {
            User::create([
              'firstname' => $this->faker->firstname,
              'lastname'  => $this->faker->lastname,
            ]);
        }

        for ($i = 0; $i < 50; $i++) {
            Group::create([
              'name'        => $this->faker->company,
              'description' => $this->faker->bs,
              'creator_id'  => User::inRandomOrder()->first()->id,
            ]);
        }

        User::get()->each(function ($user) {
            $user->group_id = Group::inRandomOrder()->first()->id;
            $user->save();
        });
    }
}
