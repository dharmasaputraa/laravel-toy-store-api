<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class AuthTestCase extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = \Database\Seeders\RolePermissionSeeder::class;

    protected function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'password' => bcrypt('password'),
            'is_active' => true,
        ], $overrides));
    }

    protected function getToken(User $user): string
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        return $guard->attempt([
            'email' => $user->email,
            'password' => 'password',
        ]);
    }

    protected function actingAsUser(User $user)
    {
        return $this->withToken($this->getToken($user));
    }

    protected function assertValidationError($response, array $fields)
    {
        $response->assertStatus(422);

        $json = $response->json();

        foreach ($fields as $field) {
            $this->assertArrayHasKey('errors', $json, 'Response does not have errors key');
            $this->assertArrayHasKey($field, $json['errors'], "Field '{$field}' not found in validation errors");
        }
    }

    /**
     * Seed regions for testing
     */
    protected function seedRegions(): void
    {
        $this->seed(\Database\Seeders\RegionTestSeeder::class);
    }
}
