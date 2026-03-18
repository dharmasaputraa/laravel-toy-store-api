<?php

namespace Tests\Feature\Api\V1\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;

class PasswordTest extends AuthTestCase
{
    public function test_forgot_password_success(): void
    {
        Queue::fake();

        $user = $this->createUser();

        Password::shouldReceive('broker')->andReturnSelf();
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_LINK_SENT);

        $response = $this->postJson(route('v1.auth.password.forgot'), [
            'email' => $user->email,
        ]);

        $response->assertOk();
    }

    public function test_reset_password_success(): void
    {
        $user = $this->createUser();
        $token = Password::createToken($user);

        Password::shouldReceive('broker')->andReturnSelf();
        Password::shouldReceive('reset')
            ->andReturn(Password::PASSWORD_RESET);

        $response = $this->postJson(route('v1.auth.password.reset'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertOk();
    }
}
