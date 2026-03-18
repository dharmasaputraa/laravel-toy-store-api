<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\Unit\UnitTestCase;

class WelcomeNotificationTest extends UnitTestCase
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
        $notification = new WelcomeNotification();

        $this->assertContains('mail', $notification->via($this->user()));
    }

    public function test_builds_correct_mail_message(): void
    {
        $notification = new WelcomeNotification();
        $mail = $notification->toMail($this->user());

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertSame('Welcome to the Toy Store!', $mail->subject);

        $this->assertStringContainsString('Hello, Test User', $mail->greeting);
        $this->assertSame('Start Shopping', $mail->actionText);
        $this->assertStringContainsString(url('/'), $mail->actionUrl);
    }

    public function test_to_array_structure(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $notification = new WelcomeNotification();
        $data = $notification->toArray($user);

        $this->assertIsArray($data);
        $this->assertSame(1, $data['user_id']);
        $this->assertSame(
            'Welcome! Your account has been successfully created.',
            $data['message']
        );
    }

    public function test_is_queued_on_emails_queue(): void
    {
        $notification = new WelcomeNotification();

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            $notification
        );

        $this->assertSame('emails', $notification->queue);
    }
}
