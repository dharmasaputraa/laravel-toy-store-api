<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\Unit\UnitTestCase;

class CustomResetPasswordNotificationTest extends UnitTestCase
{
    use RefreshDatabase;

    protected function user()
    {
        return User::factory()->make([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_uses_mail_channel(): void
    {
        $notification = new CustomResetPasswordNotification('token');

        $this->assertContains('mail', $notification->via($this->user()));
    }

    public function test_stores_token(): void
    {
        $notification = new CustomResetPasswordNotification('token123');

        $this->assertSame('token123', $notification->token);
    }

    public function test_builds_correct_mail_message(): void
    {
        config(['app.frontend_url' => 'http://localhost:3000']);

        $notification = new CustomResetPasswordNotification('token123');
        $mail = $notification->toMail($this->user());

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertSame('Reset Your Toy Store Password', $mail->subject);

        $this->assertStringContainsString('Hello, Test User', $mail->greeting);
        $this->assertStringContainsString('token123', $mail->actionUrl);
        $this->assertStringContainsString('test%40example.com', $mail->actionUrl);
        $this->assertStringContainsString('/reset-password', $mail->actionUrl);
    }

    public function test_uses_default_frontend_url_when_not_set(): void
    {
        config(['app.frontend_url' => null]);

        $notification = new CustomResetPasswordNotification('token');
        $mail = $notification->toMail($this->user());

        $this->assertStringContainsString('/reset-password', $mail->actionUrl);
    }

    public function test_action_text_is_correct(): void
    {
        $notification = new CustomResetPasswordNotification('token');

        $mail = $notification->toMail($this->user());

        $this->assertSame('Reset Password', $mail->actionText);
    }

    public function test_to_array_returns_array(): void
    {
        $notification = new CustomResetPasswordNotification('token');

        $this->assertIsArray($notification->toArray($this->user()));
    }

    public function test_is_queued_on_emails_queue(): void
    {
        $notification = new CustomResetPasswordNotification('token');

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            $notification
        );

        $this->assertSame('emails', $notification->queue);
    }
}
