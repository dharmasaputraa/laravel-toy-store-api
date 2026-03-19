<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Models\SocialAccount;
use App\Enums\RoleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Socialite
        Socialite::shouldReceive('driver')->andReturnSelf();
    }

    /**
     * Test redirect to Google OAuth provider.
     */
    public function test_redirect_to_google_provider(): void
    {
        Socialite::shouldReceive('stateless->redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/oauth/authorize'));

        $response = $this->getJson('/api/v1/auth/social/google/redirect');

        $response->assertStatus(302);
    }

    /**
     * Test redirect with invalid provider returns validation error.
     */
    public function test_redirect_with_invalid_provider(): void
    {
        $response = $this->getJson('/api/v1/auth/social/invalid/redirect');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'provider' => ['Provider tidak valid.'],
                ],
            ]);
    }

    /**
     * Test OAuth callback creates new user.
     */
    public function test_oauth_callback_creates_new_user(): void
    {
        $socialUser = new SocialUser();
        $socialUser->id = 'google-123';
        $socialUser->name = 'John Doe';
        $socialUser->email = 'john@example.com';
        $socialUser->avatar = 'https://example.com/avatar.jpg';

        Socialite::shouldReceive('stateless->user')
            ->once()
            ->andReturn($socialUser);

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'avatar',
                        'is_active',
                        'email_verified_at',
                        'created_at',
                    ],
                    'token' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                    ],
                    'is_new',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'is_active' => true,
                    ],
                    'is_new' => true,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => null,
        ]);

        $this->assertDatabaseHas('social_accounts', [
            'provider_name' => 'google',
            'provider_id' => 'google-123',
        ]);
    }

    /**
     * Test OAuth callback logs in existing user.
     */
    public function test_oauth_callback_logs_in_existing_user(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider_name' => 'google',
            'provider_id' => 'google-123',
        ]);

        $socialUser = new SocialUser();
        $socialUser->id = 'google-123';
        $socialUser->name = 'John Doe';
        $socialUser->email = 'john@example.com';
        $socialUser->avatar = 'https://example.com/avatar.jpg';

        Socialite::shouldReceive('stateless->user')
            ->once()
            ->andReturn($socialUser);

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'john@example.com',
                    ],
                    'is_new' => false,
                ],
            ]);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
    }

    /**
     * Test OAuth callback links social account to existing user by email.
     */
    public function test_oauth_callback_links_to_existing_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $socialUser = new SocialUser();
        $socialUser->id = 'google-123';
        $socialUser->name = 'John Doe';
        $socialUser->email = 'john@example.com';
        $socialUser->avatar = 'https://example.com/avatar.jpg';

        Socialite::shouldReceive('stateless->user')
            ->once()
            ->andReturn($socialUser);

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'john@example.com',
                    ],
                    'is_new' => false,
                ],
            ]);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider_name' => 'google',
            'provider_id' => 'google-123',
        ]);
    }

    /**
     * Test OAuth callback with inactive user fails.
     */
    public function test_oauth_callback_with_inactive_user_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'is_active' => false,
        ]);

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider_name' => 'google',
            'provider_id' => 'google-123',
        ]);

        $socialUser = new SocialUser();
        $socialUser->id = 'google-123';
        $socialUser->name = 'John Doe';
        $socialUser->email = 'john@example.com';
        $socialUser->avatar = 'https://example.com/avatar.jpg';

        Socialite::shouldReceive('stateless->user')
            ->once()
            ->andReturn($socialUser);

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['Account is disabled.'],
                ],
            ]);
    }

    /**
     * Test linking social account to authenticated user.
     */
    public function test_link_social_account(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        /** @var string $token */
        $token = Auth::guard('api')->login($user);

        $socialUser = new SocialUser();
        $socialUser->id = 'google-456';
        $socialUser->name = 'John Doe';
        $socialUser->email = 'john@example.com';
        $socialUser->avatar = 'https://example.com/avatar.jpg';

        Socialite::shouldReceive('userFromToken')
            ->once()
            ->andReturn($socialUser);

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/social/link', [
                'provider' => 'google',
                'access_token' => 'google-access-token',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'provider_name',
                    'provider_id',
                    'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'provider_name' => 'google',
                    'provider_id' => 'google-456',
                ],
            ]);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider_name' => 'google',
            'provider_id' => 'google-456',
        ]);
    }

    /**
     * Test linking duplicate social account fails.
     */
    public function test_link_duplicate_social_account_fails(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider_name' => 'google',
            'provider_id' => 'google-123',
        ]);

        /** @var string $token */
        $token = Auth::guard('api')->login($user);

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/social/link', [
                'provider' => 'google',
                'access_token' => 'google-access-token',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'provider' => ['This account is already connected to this provider.'],
                ],
            ]);
    }

    /**
     * Test linking social account that belongs to another user fails.
     */
    public function test_link_social_account_belongs_to_another_user_fails(): void
    {
        /** @var \App\Models\User $user1 */
        $user1 = User::factory()->create();
        /** @var \App\Models\User $user2 */
        $user2 = User::factory()->create();

        SocialAccount::factory()->create([
            'user_id' => $user2->id,
            'provider_name' => 'google',
            'provider_id' => 'google-123',
        ]);

        /** @var string $token */
        $token = Auth::guard('api')->login($user1);

        $socialUser = new SocialUser();
        $socialUser->id = 'google-123';
        $socialUser->name = 'John Doe';
        $socialUser->email = 'john@example.com';
        $socialUser->avatar = 'https://example.com/avatar.jpg';

        Socialite::shouldReceive('userFromToken')
            ->once()
            ->andReturn($socialUser);

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/social/link', [
                'provider' => 'google',
                'access_token' => 'google-access-token',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'provider' => ['This social account is already connected to another user.'],
                ],
            ]);
    }

    /**
     * Test unlinking social account.
     */
    public function test_unlink_social_account(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider_name' => 'google',
            'provider_id' => 'google-123',
        ]);

        /** @var string $token */
        $token = Auth::guard('api')->login($user);

        $response = $this->withToken($token)
            ->deleteJson('/api/v1/auth/social/unlink/google');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Social account unlinked successfully',
            ]);

        $this->assertDatabaseMissing('social_accounts', [
            'user_id' => $user->id,
            'provider_name' => 'google',
        ]);
    }

    /**
     * Test unlinking non-existent social account fails.
     */
    public function test_unlink_nonexistent_social_account_fails(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        /** @var string $token */
        $token = Auth::guard('api')->login($user);

        $response = $this->withToken($token)
            ->deleteJson('/api/v1/auth/social/unlink/google');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'provider' => ['Akun social ini tidak ditemukan.'],
                ],
            ]);
    }

    /**
     * Test unlinking last social account without password fails.
     */
    public function test_unlink_last_social_account_without_password_fails(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => null, // No password
        ]);

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider_name' => 'google',
            'provider_id' => 'google-123',
        ]);

        /** @var string $token */
        $token = Auth::guard('api')->login($user);

        $response = $this->withToken($token)
            ->deleteJson('/api/v1/auth/social/unlink/google');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'provider' => ['Tidak bisa memutus koneksi social account. Anda harus memiliki password atau minimal satu social account aktif.'],
                ],
            ]);
    }

    /**
     * Test getting linked accounts.
     */
    public function test_get_linked_accounts(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider_name' => 'google',
            'provider_id' => 'google-123',
        ]);

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider_name' => 'facebook',
            'provider_id' => 'fb-456',
        ]);

        /** @var string $token */
        $token = Auth::guard('api')->login($user);

        $response = $this->withToken($token)
            ->getJson('/api/v1/auth/social/accounts');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Linked accounts retrieved successfully',
                'data' => [
                    'providers' => ['facebook', 'google'], // Order may vary
                ],
            ]);
    }

    /**
     * Test getting linked accounts returns empty array for user without social accounts.
     */
    public function test_get_linked_accounts_returns_empty_for_user_without_social_accounts(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        /** @var string $token */
        $token = Auth::guard('api')->login($user);

        $response = $this->withToken($token)
            ->getJson('/api/v1/auth/social/accounts');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Linked accounts retrieved successfully',
                'data' => [
                    'providers' => [],
                ],
            ]);
    }

    /**
     * Test OAuth callback assigns default role to new user.
     */
    public function test_oauth_callback_assigns_default_role_to_new_user(): void
    {
        $socialUser = new SocialUser();
        $socialUser->id = 'google-123';
        $socialUser->name = 'John Doe';
        $socialUser->email = 'john2@example.com'; // Use unique email
        $socialUser->avatar = 'https://example.com/avatar.jpg';

        Socialite::shouldReceive('stateless->user')
            ->once()
            ->andReturn($socialUser);

        $this->getJson('/api/v1/auth/social/google/callback');

        $user = User::where('email', 'john2@example.com')->first();

        $this->assertTrue($user->hasRole(RoleType::CUSTOMER->value));
    }
}
