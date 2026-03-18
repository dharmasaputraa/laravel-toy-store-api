<?php

namespace Tests\Unit\Events;

use App\Events\UserRegistered;
use Tests\Unit\UnitTestCase;

class UserRegisteredTest extends UnitTestCase
{
    public function test_event_stores_user(): void
    {
        $user = $this->createUser([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $event = new UserRegistered($user);

        $this->assertSame($user, $event->user);
    }

    public function test_event_broadcasts_on_private_auth_channel(): void
    {
        $event = new UserRegistered($this->createUser());

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('private-auth', $channels[0]->name);
    }
}
