<?php

namespace Tests\Feature\Api\V1\Auth;

class LoginTest extends AuthTestCase
{
    public function test_login_success(): void
    {
        $user = $this->createUser();

        $response = $this->postJson(route('v1.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'token' => ['access_token'],
                ]
            ]);
    }

    public function test_login_invalid_credentials(): void
    {
        $user = $this->createUser();

        $response = $this->postJson(route('v1.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong',
        ]);

        $this->assertValidationError($response, ['email']);
    }

    public function test_login_inactive_user(): void
    {
        $user = $this->createUser(['is_active' => false]);

        $response = $this->postJson(route('v1.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertValidationError($response, ['email']);
    }
}
