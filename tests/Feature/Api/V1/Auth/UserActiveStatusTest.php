<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Api\V1\Auth\AuthTestCase;

class UserActiveStatusTest extends AuthTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_inactive_user_cannot_login()
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        // Act
        $response = $this->postJson(route('v1.auth.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['Account is disabled.'],
                ],
            ]);
    }

    public function test_active_user_can_login()
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Act
        $response = $this->postJson(route('v1.auth.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'avatar',
                        'is_active',
                        'locale',
                        'email_verified_at',
                        'is_email_verified',
                        'roles',
                        'created_at',
                    ],
                    'token' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'email' => $user->email,
            ]);
    }

    public function test_inactive_user_cannot_access_protected_route()
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Login and get token
        $loginResponse = $this->postJson(route('v1.auth.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token.access_token');

        // Deactivate user
        $user->update(['is_active' => false]);

        // Act - Try to access protected route
        $response = $this->withToken($token)
            ->getJson(route('v1.user.me'));

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['Account is disabled.'],
                ],
            ]);
    }

    public function test_active_user_can_access_protected_route()
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Login and get token
        $loginResponse = $this->postJson(route('v1.auth.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token.access_token');

        // Act - Access protected route
        $response = $this->withToken($token)
            ->getJson(route('v1.user.me'));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'avatar',
                    'is_active',
                    'locale',
                    'email_verified_at',
                    'is_email_verified',
                    'roles',
                    'created_at',
                ],
            ])
            ->assertJsonFragment([
                'email' => $user->email,
                'is_active' => true,
            ]);
    }

    public function test_inactive_user_cannot_refresh_token()
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Login and get token
        $loginResponse = $this->postJson(route('v1.auth.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token.access_token');

        // Deactivate user
        $user->update(['is_active' => false]);

        // Act - Try to refresh token
        $response = $this->withToken($token)
            ->postJson(route('v1.auth.token.refresh'));

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['Account is disabled.'],
                ],
            ]);
    }

    public function test_inactive_social_login_user_cannot_access_api()
    {
        // Arrange - Create user as if they logged in via social auth
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => false,
        ]);

        // Manually create a token for the user (simulating social login)
        /** @var \Tymon\JWTAuth\JWTAuth $guard */
        $guard = auth('api');
        $token = $guard->login($user);

        // Act - Try to access protected route
        $response = $this->withToken($token)
            ->getJson(route('v1.user.me'));

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['Account is disabled.'],
                ],
            ]);
    }
}
