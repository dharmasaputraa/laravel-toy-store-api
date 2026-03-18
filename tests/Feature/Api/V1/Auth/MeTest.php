<?php

namespace Tests\Feature\Api\V1\Auth;

class MeTest extends AuthTestCase
{
    public function test_me_success(): void
    {
        $user = $this->createUser();

        $response = $this->actingAsUser($user)
            ->getJson($this->url('me'));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ]
            ]);
    }

    public function test_me_requires_auth(): void
    {
        $this->getJson($this->url('me'))
            ->assertUnauthorized();
    }
}
