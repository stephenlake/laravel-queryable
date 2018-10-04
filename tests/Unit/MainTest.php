<?php

namespace Queryable\Tests\Unit;

use Queryable\Tests\TestCase;
use Queryable\Tests\Models\Group;
use Queryable\Tests\Models\User;

class MainTest extends TestCase
{
    public function test_attributes_like_exact_case()
    {
        $groups = Group::inRandomOrder()->take(5);
        $group = $groups->first();

        $wordFromGroupName = array_random(explode(' ', $group->first()->name));
        $wordFromGroupDescription = array_random(explode(' ', $group->first()->description));

        $filters = [
          "name=*{$wordFromGroupName}*",
          "description=*{$wordFromGroupDescription}*"
        ];

        $groups = Group::withFilters(['name', 'description'], $filters)->get();

        $this->assertTrue($groups->count() > 0);

        $groups->each(function ($group) use ($wordFromGroupDescription, $wordFromGroupName) {
            $this->assertTrue(str_contains($group->name, $wordFromGroupName));
            $this->assertTrue(str_contains($group->description, $wordFromGroupDescription));
        });
    }

    public function test_attributes_like_ignore_case()
    {
        $groups = Group::inRandomOrder()->take(5);
        $group = $groups->first();

        $wordFromGroupName = strtoupper(array_random(explode(' ', $group->first()->name)));
        $wordFromGroupDescription = strtoupper(array_random(explode(' ', $group->first()->description)));

        $filters = [
          "name=*{$wordFromGroupName}*",
          "description=*{$wordFromGroupDescription}*"
        ];

        $groups = Group::withFilters(['name', 'description'], $filters)->get();

        $this->assertTrue($groups->count() > 0);

        $groups->each(function ($group) use ($wordFromGroupDescription, $wordFromGroupName) {
            $this->assertTrue(str_contains(strtolower($group->name), strtolower($wordFromGroupName)));
            $this->assertTrue(str_contains(strtolower($group->description), strtolower($wordFromGroupDescription)));
        });
    }

    public function test_attributes_relationship_exact_case()
    {
        $user = User::inRandomOrder()->with('group')->first();
        $group = $user->group;

        $wordFromGroupName = strtoupper(array_random(explode(' ', $group->first()->name)));

        $filters = [
          "group.name=*{$wordFromGroupName}*",
          "group.creator_id!=0"
        ];

        $users = User::with('group')->withFilters(['group.name', 'group.creator_id'], $filters)->get();

        $this->assertTrue($users->count() > 0);

        $users->each(function ($user) use ($wordFromGroupName) {
            $this->assertTrue(str_contains(strtolower($user->group->name), strtolower($wordFromGroupName)));
        });
    }
}
