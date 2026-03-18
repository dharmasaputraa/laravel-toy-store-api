<?php

namespace Tests\Feature\Api\V1\User;

use Tests\Feature\Api\V1\Auth\AuthTestCase;

class UpdateProfileTest extends AuthTestCase
{

    public function test_update_profile_success(): void
    {
        $user = $this->createUser([
            'name' => 'Old Name',
            'phone' => '123456789',
            'locale' => 'en',
        ]);

        $response = $this->actingAsUser($user)
            ->putJson(route('v1.user.profile.update'), [
                'name' => 'New Name',
                'phone' => '987654321',
                'locale' => 'id',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'New Name',
                    'phone' => '987654321',
                    'locale' => 'id',
                ]
            ]);

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('987654321', $user->phone);
        $this->assertEquals('id', $user->locale);
    }

    public function test_update_profile_partial_fields(): void
    {
        $user = $this->createUser([
            'name' => 'Old Name',
            'phone' => '123456789',
            'locale' => 'en',
        ]);

        $response = $this->actingAsUser($user)
            ->putJson(route('v1.user.profile.update'), [
                'name' => 'Updated Name Only',
                'phone' => '123456789',
                'locale' => 'en',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name Only',
                    'phone' => '123456789',
                    'locale' => 'en',
                ]
            ]);

        $user->refresh();
        $this->assertEquals('Updated Name Only', $user->name);
        $this->assertEquals('123456789', $user->phone);
        $this->assertEquals('en', $user->locale);
    }

    public function test_update_profile_requires_auth(): void
    {
        $this->putJson(route('v1.user.profile.update'), [
            'name' => 'New Name',
        ])->assertUnauthorized();
    }

    public function test_update_profile_does_not_change_email(): void
    {
        $user = $this->createUser([
            'email' => 'old@example.com',
            'name' => 'Old Name',
        ]);

        $response = $this->actingAsUser($user)
            ->putJson(route('v1.user.profile.update'), [
                'name' => 'New Name',
                'email' => 'new@example.com', // This should be ignored
            ]);

        $response->assertOk();

        $user->refresh();
        $this->assertEquals('old@example.com', $user->email);
        $this->assertEquals('New Name', $user->name);
    }


    public function test_update_profile_special_characters_in_name(): void
    {
        $user = $this->createUser(['name' => 'Old Name']);

        $response = $this->actingAsUser($user)
            ->putJson(route('v1.user.profile.update'), [
                'name' => 'José María González',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'José María González',
                ]
            ]);

        $user->refresh();
        $this->assertEquals('José María González', $user->name);
    }
}
