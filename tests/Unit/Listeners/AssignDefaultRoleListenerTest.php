<?php

namespace Tests\Unit\Listeners;

use App\Enums\RoleType;
use App\Events\UserRegistered;
use App\Listeners\AssignDefaultRoleListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\UnitTestCase;

class AssignDefaultRoleListenerTest extends UnitTestCase
{
    use RefreshDatabase;

    public function test_it_assigns_customer_role(): void
    {
        $user = $this->createUserPersisted();

        (new AssignDefaultRoleListener())->handle(new UserRegistered($user));

        $this->assertTrue($user->fresh()->hasRole(RoleType::CUSTOMER->value));
    }

    public function test_it_does_not_assign_other_roles(): void
    {
        $user = $this->createUserPersisted();

        (new AssignDefaultRoleListener())->handle(new UserRegistered($user));

        $user = $user->fresh();

        $this->assertFalse($user->hasRole(RoleType::WAREHOUSE->value));
        $this->assertFalse($user->hasRole(RoleType::ADMIN->value));
        $this->assertFalse($user->hasRole(RoleType::SUPER_ADMIN->value));
    }
}
