<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Unit\UnitTestCase;

class UserTest extends UnitTestCase
{
    use RefreshDatabase;

    /**
     * Password & Security
     */
    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'plaintext',
        ]);

        $this->assertNotSame('plaintext', $user->password);
        $this->assertTrue(Hash::check('plaintext', $user->password));
    }

    public function test_password_is_hidden(): void
    {
        $user = User::factory()->make();

        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_remember_token_is_hidden(): void
    {
        $user = User::factory()->make();

        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    /**
     * JWT
     */
    public function test_jwt_identifier_returns_user_key(): void
    {
        $user = User::factory()->create(['id' => 123]);

        $this->assertSame(123, $user->getJWTIdentifier());
    }

    public function test_jwt_custom_claims_are_empty(): void
    {
        $user = User::factory()->make();

        $this->assertSame([], $user->getJWTCustomClaims());
    }

    /**
     * Notifications
     */
    public function test_sends_custom_password_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $token = 'reset-token';

        $user->sendPasswordResetNotification($token);

        Notification::assertSentTo(
            $user,
            CustomResetPasswordNotification::class,
            fn($notification) => $notification->token === $token
        );
    }

    public function test_does_not_use_default_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->sendPasswordResetNotification('token');

        Notification::assertNotSentTo(
            $user,
            \Illuminate\Auth\Notifications\ResetPassword::class
        );
    }

    /**
     * Relationships
     */
    public function test_has_addresses_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->addresses()
        );
    }

    /**
     * Soft Deletes
     */
    public function test_soft_delete_works(): void
    {
        $user = User::factory()->create();

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /**
     * Casts
     */
    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => '2024-01-01 00:00:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
    }

    /**
     * Roles (Spatie)
     */
    public function test_can_assign_and_check_role(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasRole('customer'));

        $user->assignRole('customer');

        $this->assertTrue($user->hasRole('customer'));
    }
}
