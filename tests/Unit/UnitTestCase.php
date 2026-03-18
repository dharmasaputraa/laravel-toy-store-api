<?php

namespace Tests\Unit;

use Tests\TestCase;

abstract class UnitTestCase extends TestCase
{
    protected function createUser(array $overrides = [])
    {
        return \App\Models\User::factory()->make($overrides);
    }

    protected function createUserPersisted(array $overrides = [])
    {
        return \App\Models\User::factory()->create($overrides);
    }
}
