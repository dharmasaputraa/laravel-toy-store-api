<?php

namespace Tests\Feature\Api\V1\Auth;

class ThrottleTest extends AuthTestCase
{
    public function test_register_throttle(): void
    {
        $this->app['env'] = 'production';

        for ($i = 0; $i < 3; $i++) {
            $this->postJson($this->url('register'), [
                'name' => 'Test',
                'email' => "test{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
        }

        $this->postJson($this->url('register'), [
            'name' => 'Test',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(429);
    }

    public function test_login_throttle(): void
    {
        $this->app['env'] = 'production';

        $user = $this->createUser(['email' => 'test@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson($this->url('login'), [
                'email' => $user->email,
                'password' => 'wrong',
            ]);
        }

        $response = $this->postJson($this->url('login'), [
            'email' => $user->email,
            'password' => 'wrong',
        ]);

        $this->assertValidationError($response, ['email']);
    }
}
