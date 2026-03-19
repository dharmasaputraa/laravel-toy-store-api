<?php

namespace Tests\Feature\Api\V1\User;

use Illuminate\Support\Facades\Hash;
use Tests\Feature\Api\V1\Auth\AuthTestCase;

class ChangePasswordTest extends AuthTestCase
{

    public function test_change_password_success(): void
    {
        $user = $this->createUser(['password' => bcrypt('old_password')]);

        // Generate token with custom password
        $token = $this->getCustomToken($user, 'old_password');

        $response = $this->withToken($token)
            ->postJson(route('v1.user.profile.password.change'), [
                'current_password' => 'old_password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'message' => 'Password changed successfully. Please login again.',
                ]
            ]);

        // Verify password was changed
        $user->refresh();
        $this->assertNotTrue(Hash::check('old_password', $user->password));
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }

    public function test_change_password_incorrect_current_password(): void
    {
        $user = $this->createUser(['password' => bcrypt('correct_password')]);

        $token = $this->getCustomToken($user, 'correct_password');

        $response = $this->withToken($token)
            ->postJson(route('v1.user.profile.password.change'), [
                'current_password' => 'wrong_password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertUnprocessable();
    }

    public function test_change_password_requires_current_password(): void
    {
        $user = $this->createUser();

        $response = $this->actingAsUser($user)
            ->postJson(route('v1.user.profile.password.change'), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertUnprocessable();
    }

    public function test_change_password_requires_new_password(): void
    {
        $user = $this->createUser(['password' => bcrypt('old_password')]);

        $token = $this->getCustomToken($user, 'old_password');

        $response = $this->withToken($token)
            ->postJson(route('v1.user.profile.password.change'), [
                'current_password' => 'old_password',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertUnprocessable();
    }

    public function test_change_password_requires_password_confirmation(): void
    {
        $user = $this->createUser(['password' => bcrypt('old_password')]);

        $token = $this->getCustomToken($user, 'old_password');

        $response = $this->withToken($token)
            ->postJson(route('v1.user.profile.password.change'), [
                'current_password' => 'old_password',
                'password' => 'NewPassword123!',
            ]);

        $response->assertUnprocessable();
    }

    public function test_change_password_password_confirmation_must_match(): void
    {
        $user = $this->createUser(['password' => bcrypt('old_password')]);

        $token = $this->getCustomToken($user, 'old_password');

        $response = $this->withToken($token)
            ->postJson(route('v1.user.profile.password.change'), [
                'current_password' => 'old_password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ]);

        $response->assertUnprocessable();
    }

    public function test_change_password_requires_auth(): void
    {
        $this->postJson(route('v1.user.profile.password.change'), [
            'current_password' => 'old_password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertUnauthorized();
    }

    public function test_change_password_invalidates_token(): void
    {
        $user = $this->createUser(['password' => bcrypt('old_password')]);

        // Get the token before changing password
        $token = $this->getCustomToken($user, 'old_password');

        // Change password
        $response = $this->withToken($token)
            ->postJson(route('v1.user.profile.password.change'), [
                'current_password' => 'old_password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertOk();

        // Try to use the old token - should fail
        $this->withToken($token)
            ->getJson('/api/v1/profile')
            ->assertUnauthorized();
    }

    /**
     * Generate a JWT token for a user with a custom password
     */
    protected function getCustomToken(\App\Models\User $user, string $password): string
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        return $guard->attempt([
            'email' => $user->email,
            'password' => $password,
        ]);
    }
}
