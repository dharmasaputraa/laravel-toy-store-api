<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class EmailVerificationTest extends AuthTestCase
{
    public function test_verify_email_success(): void
    {
        $user = $this->createUser(['email_verified_at' => null]);

        $verificationUrl = URL::signedRoute(
            'v1.auth.email.verification.verify',
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Email verified successfully',
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_verify_email_invalid_hash(): void
    {
        $user = $this->createUser(['email_verified_at' => null]);

        $verificationUrl = URL::signedRoute(
            'v1.auth.email.verification.verify',
            [
                'id' => $user->id,
                'hash' => 'invalid-hash',
            ]
        );

        $response = $this->getJson($verificationUrl);

        $this->assertValidationError($response, ['email']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email_verified_at' => null,
        ]);
    }

    public function test_verify_email_invalid_signature(): void
    {
        $user = $this->createUser(['email_verified_at' => null]);

        $verificationUrl = route('v1.auth.email.verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]) . '?signature=invalid';

        $response = $this->getJson($verificationUrl);

        // Signed middleware returns 403 for invalid signatures
        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email_verified_at' => null,
        ]);
    }

    public function test_verify_email_already_verified(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);

        $verificationUrl = URL::signedRoute(
            'v1.auth.email.verification.verify',
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->getJson($verificationUrl);

        $this->assertValidationError($response, ['email']);
    }

    public function test_verify_email_nonexistent_user(): void
    {
        $verificationUrl = URL::signedRoute(
            'v1.auth.email.verification.verify',
            [
                'id' => 99999,
                'hash' => sha1('nonexistent@example.com'),
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertNotFound();
    }

    public function test_resend_verification_notification_success(): void
    {
        Notification::fake();

        $user = $this->createUser(['email_verified_at' => null]);
        $token = $this->getToken($user);

        $response = $this->withToken($token)
            ->postJson(route('v1.auth.email.verification.resend'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Verification link sent successfully',
            ]);

        Notification::assertSentTo(
            $user,
            VerifyEmailNotification::class
        );
    }

    public function test_resend_verification_notification_already_verified(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $token = $this->getToken($user);

        $response = $this->withToken($token)
            ->postJson(route('v1.auth.email.verification.resend'));

        // The service throws ValidationException which returns 422
        $this->assertValidationError($response, ['email']);
    }

    public function test_resend_verification_notification_unauthenticated(): void
    {
        $response = $this->postJson(route('v1.auth.email.verification.resend'));

        $response->assertUnauthorized();
    }

    public function test_user_response_includes_email_verification_status(): void
    {
        $unverifiedUser = $this->createUser(['email_verified_at' => null]);
        $token = $this->getToken($unverifiedUser);

        $response = $this->withToken($token)
            ->getJson(route('v1.user.profile'));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $unverifiedUser->id,
                    'email' => $unverifiedUser->email,
                    'email_verified_at' => null,
                    'is_email_verified' => false,
                ],
            ]);

        $verifiedUser = $this->createUser(['email_verified_at' => now()]);
        $token = $this->getToken($verifiedUser);

        $response = $this->withToken($token)
            ->getJson(route('v1.user.profile'));

        $response->assertOk();
        $verifiedUser->refresh();
        $this->assertNotNull($verifiedUser->email_verified_at);
        $this->assertTrue($response->json('data.is_email_verified'));
    }

    public function test_register_sends_verification_notification(): void
    {
        Notification::fake();

        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $this->postJson(route('v1.auth.register'), $data);

        $user = User::where('email', 'test@example.com')->first();

        Notification::assertSentTo(
            $user,
            VerifyEmailNotification::class,
            function ($notification) use ($user) {
                $verificationUrl = $notification->toMail($user)->actionUrl;

                return str_contains($verificationUrl, $user->id)
                    && str_contains($verificationUrl, sha1($user->email));
            }
        );
    }
}
