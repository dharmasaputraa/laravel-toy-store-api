<?php

namespace Tests\Feature\Api\V1\Auth;

use Illuminate\Support\Facades\Queue;

class RegisterTest extends AuthTestCase
{
    public function test_register_success(): void
    {
        Queue::fake();

        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson($this->url('register'), $data);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_validation(): void
    {
        $this->assertValidationError(
            $this->postJson($this->url('register'), []),
            ['name', 'email', 'password']
        );
    }

    public function test_register_duplicate_email(): void
    {
        $this->createUser(['email' => 'test@example.com']);

        $response = $this->postJson($this->url('register'), [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertValidationError($response, ['email']);
    }
}
