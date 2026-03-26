<?php

namespace Tests\Feature\Api\V1\Health;

use App\Enums\RoleType;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Api\V1\Auth\AuthTestCase;

class HealthTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_basic_health_success(): void
    {
        $this->getJson(route('v1.health.basic'))
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ])
            ->assertJson([
                'success' => true
            ]);
    }

    public function test_full_health_requires_auth(): void
    {
        $this->getJson(route('v1.health.full'))
            ->assertUnauthorized();
    }

    public function test_full_health_requires_super_admin(): void
    {
        $user = $this->createUser();

        $this->actingAsUser($user)
            ->getJson(route('v1.health.full'))
            ->assertForbidden();
    }

    public function test_full_health_success_for_super_admin(): void
    {
        $user = $this->createUser();
        $user->assignRole(RoleType::SUPER_ADMIN->value);

        $this->actingAsUser($user)
            ->getJson(route('v1.health.full'))
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);
    }
}
