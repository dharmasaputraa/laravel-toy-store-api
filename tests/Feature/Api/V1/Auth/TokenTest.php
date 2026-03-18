<?php

namespace Tests\Feature\Api\V1\Auth;

class TokenTest extends AuthTestCase
{
    public function test_refresh_success(): void
    {
        $user = $this->createUser();

        $response = $this->actingAsUser($user)
            ->postJson(route('v1.auth.token.refresh'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['access_token']
            ]);
    }

    public function test_refresh_requires_auth(): void
    {
        $this->postJson(route('v1.auth.token.refresh'))
            ->assertUnauthorized();
    }

    public function test_logout_revokes_token(): void
    {
        $user = $this->createUser();
        $token = $this->getToken($user);

        $this->withToken($token)
            ->postJson(route('v1.auth.token.revoke'))
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/profile')
            ->assertUnauthorized();
    }
}
