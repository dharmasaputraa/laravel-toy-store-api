<?php

namespace Tests\Feature\Api\V1\User;

use Tests\Feature\Api\V1\Auth\AuthTestCase;

class MeTest extends AuthTestCase
{
    protected string $baseUrl = '/api/v1/profile';
    public function test_me_success(): void
    {
        $user = $this->createUser();

        $response = $this->actingAsUser($user)
            ->getJson($this->url());

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
        $this->getJson($this->url())
            ->assertUnauthorized();
    }
}
