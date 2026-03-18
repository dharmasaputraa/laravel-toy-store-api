<?php

namespace Tests\Feature\Api\V1\Health;

use App\Enums\RoleType;
use Tests\Feature\Api\V1\Auth\AuthTestCase;

class HealthTest extends AuthTestCase
{
    protected string $baseUrl = '/api/v1/health';

    public function test_basic_health_success(): void
    {
        $this->getJson($this->url(''))
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
        $this->getJson($this->url('full'))
            ->assertUnauthorized();
    }

    public function test_full_health_requires_super_admin(): void
    {
        $user = $this->createUser();

        $this->actingAsUser($user)
            ->getJson($this->url('full'))
            ->assertForbidden();
    }

    public function test_full_health_success_for_super_admin(): void
    {
        $user = $this->createUser();
        $user->assignRole(RoleType::SUPER_ADMIN->value);

        $this->actingAsUser($user)
            ->getJson($this->url('full'))
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);
    }
}
