<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Tests\Unit\UnitTestCase;

class VerifyEmailNotificationTest extends UnitTestCase
{
    use RefreshDatabase;

    protected function user()
    {
        return User::factory()->make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'id' => 123,
        ]);
    }

    public function test_uses_mail_channel(): void
    {
        $notification = new VerifyEmailNotification();

        $this->assertContains('mail', $notification->via($this->user()));
    }

    public function test_builds_correct_mail_message(): void
    {
        config(['app.frontend_url' => 'http://localhost:3000']);

        URL::shouldReceive('signedRoute')
            ->once()
            ->with(
                'v1.auth.email.verification.verify',
                \Mockery::on(function ($params) {
                    return isset($params['id']) && isset($params['hash']);
                }),
                \Mockery::type('object')
            )
            ->andReturn('https://api.example.com/verify/123/hash123');

        $notification = new VerifyEmailNotification();
        $mail = $notification->toMail($this->user());

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertSame('Verify Your Toy Store Email Address', $mail->subject);
        $this->assertStringContainsString('Hello, Test User', $mail->greeting);
        $this->assertStringContainsString('Thank you for registering with Toy Store!', $mail->introLines[0]);
        $this->assertStringContainsString('Please click the button below to verify your email address.', $mail->introLines[1]);
        $this->assertSame('Verify Email Address', $mail->actionText);
        $this->assertStringContainsString('http://localhost:3000/verify', $mail->actionUrl);
    }

    public function test_generates_verification_url_with_signed_route(): void
    {
        config(['app.frontend_url' => 'https://example.com']);

        URL::shouldReceive('signedRoute')
            ->once()
            ->with(
                'v1.auth.email.verification.verify',
                \Mockery::on(function ($params) {
                    return $params['id'] === 123 &&
                        $params['hash'] === sha1('test@example.com');
                }),
                \Mockery::type('object')
            )
            ->andReturn('https://api.example.com/verify/123/hash123');

        $notification = new VerifyEmailNotification();
        $url = $notification->toMail($this->user())->actionUrl;

        // URL is URL-encoded
        $this->assertStringStartsWith('https://example.com/verify?url=', $url);
        $this->assertStringContainsString('https%3A%2F%2Fapi.example.com%2Fverify%2F123%2Fhash123', $url);
    }

    public function test_uses_default_frontend_url_when_not_configured(): void
    {
        config(['app.frontend_url' => null]);

        URL::shouldReceive('signedRoute')
            ->once()
            ->andReturn('https://api.example.com/verify/123/hash123');

        $notification = new VerifyEmailNotification();
        $mail = $notification->toMail($this->user());

        // When null is set, it uses the default from config which might be empty string
        $this->assertStringContainsString('/verify?url=', $mail->actionUrl);
        $this->assertStringContainsString('https%3A%2F%2Fapi.example.com%2Fverify%2F123%2Fhash123', $mail->actionUrl);
    }

    public function test_verification_url_expires_in_24_hours(): void
    {
        URL::shouldReceive('signedRoute')
            ->once()
            ->with(
                'v1.auth.email.verification.verify',
                \Mockery::type('array'),
                \Mockery::on(function ($expires) {
                    return $expires->is(now()->addHours(24));
                })
            )
            ->andReturn('https://api.example.com/verify');

        $notification = new VerifyEmailNotification();
        $notification->toMail($this->user());
    }

    public function test_to_array_returns_empty_array(): void
    {
        $notification = new VerifyEmailNotification();

        $this->assertIsArray($notification->toArray($this->user()));
        $this->assertEmpty($notification->toArray($this->user()));
    }

    public function test_is_queued_on_emails_queue(): void
    {
        $notification = new VerifyEmailNotification();

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            $notification
        );

        $this->assertSame('emails', $notification->queue);
    }

    public function test_includes_security_warning_in_mail(): void
    {
        URL::shouldReceive('signedRoute')->once()->andReturn('https://api.example.com/verify');

        $notification = new VerifyEmailNotification();
        $mail = $notification->toMail($this->user());

        $this->assertStringContainsString(
            'If you did not create an account, no further action is required.',
            $mail->outroLines[0]
        );
    }

    public function test_generates_correct_hash_for_email_verification(): void
    {
        $user = $this->user();

        URL::shouldReceive('signedRoute')
            ->once()
            ->with(
                'v1.auth.email.verification.verify',
                \Mockery::on(function ($params) use ($user) {
                    return $params['hash'] === sha1($user->getEmailForVerification());
                }),
                \Mockery::type('object')
            )
            ->andReturn('https://api.example.com/verify');

        $notification = new VerifyEmailNotification();
        $notification->toMail($user);
    }
}
