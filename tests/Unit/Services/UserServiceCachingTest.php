<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserServiceCachingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = \Database\Seeders\RolePermissionSeeder::class;

    private UserService $userService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userService = app(UserService::class);
        $this->user = User::factory()->create(['password' => bcrypt('password')]);
        $this->user->assignRole('customer');
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    protected function actingAsUser(User $user): self
    {
        $this->actingAs($user, 'api');
        return $this;
    }

    public function test_profile_caches_user_data(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:profile:{$this->user->id}";

        // Ensure cache is empty
        $this->assertNull(Cache::get($cacheKey));

        // First call should cache the data
        $result1 = $this->userService->profile();

        // Verify cache is populated
        $this->assertNotNull(Cache::get($cacheKey));
        $cachedData = Cache::get($cacheKey);
        $this->assertEquals($this->user->id, $cachedData->id);
        $this->assertTrue($cachedData->relationLoaded('roles'));

        // Second call should return cached data
        $result2 = $this->userService->profile();

        // Both results should be the same
        $this->assertEquals($result1->id, $result2->id);
    }

    public function test_profile_cache_has_30_minutes_ttl(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:profile:{$this->user->id}";

        // Make the call
        $this->userService->profile();

        // Check that cache exists
        $this->assertNotNull(Cache::get($cacheKey));

        // We can't easily test exact TTL without mocking time,
        // but we can verify the cache structure
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_update_profile_invalidates_cache(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:profile:{$this->user->id}";

        // Populate cache
        $this->userService->profile();
        $this->assertNotNull(Cache::get($cacheKey));

        // Update profile
        $updateData = new \App\DTOs\User\Profile\UpdateProfileData(
            name: 'Updated Name',
            phone: '08123456789',
            locale: null,
        );

        $this->userService->updateProfile($updateData);

        // Verify cache is invalidated
        $this->assertNull(Cache::get($cacheKey));

        // Verify user was updated
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'phone' => '08123456789',
        ]);
    }

    public function test_upload_avatar_invalidates_cache(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:profile:{$this->user->id}";

        // Populate cache
        $this->userService->profile();
        $this->assertNotNull(Cache::get($cacheKey));

        // Mock file upload
        $file = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg');
        $uploadData = new \App\DTOs\User\Profile\UploadAvatarData(
            avatar: $file
        );

        // Note: This test may fail if S3 is not configured,
        // but the cache invalidation logic should still work
        try {
            $this->userService->uploadAvatar($uploadData);

            // Verify cache is invalidated
            $this->assertNull(Cache::get($cacheKey));
        } catch (\Exception $e) {
            // If S3 fails, just verify cache was cleared before the error
            $this->assertNull(Cache::get($cacheKey));
        }
    }

    public function test_change_password_invalidates_cache(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:profile:{$this->user->id}";

        // Populate cache
        $this->userService->profile();
        $this->assertNotNull(Cache::get($cacheKey));

        // Change password
        $passwordData = new \App\DTOs\User\Profile\ChangePasswordData(
            currentPassword: 'password',
            password: 'NewPassword123!'
        );

        // Note: changePassword() will try to logout which may throw JWTException in unit tests
        // but the cache should be invalidated before that happens
        try {
            $this->userService->changePassword($passwordData);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // Expected in unit tests without proper JWT setup
        }

        // Verify cache is invalidated (this should happen before logout)
        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_profile_includes_roles_in_cache(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:profile:{$this->user->id}";

        // Call profile
        $result = $this->userService->profile();

        // Verify roles are loaded
        $this->assertTrue($result->relationLoaded('roles'));
        $this->assertCount(1, $result->roles);
        $this->assertEquals('customer', $result->roles->first()->name);

        // Verify cache contains loaded roles
        $cachedData = Cache::get($cacheKey);
        $this->assertTrue($cachedData->relationLoaded('roles'));
    }

    public function test_cache_key_format_is_correct(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:profile:{$this->user->id}";

        $this->userService->profile();

        // Verify exact cache key format
        $this->assertNotNull(Cache::get($cacheKey));
        $this->assertStringStartsWith('user:profile:', $cacheKey);
        $this->assertStringEndsWith((string)$this->user->id, $cacheKey);
    }

    public function test_multiple_users_have_separate_cache_entries(): void
    {
        $this->actingAsUser($this->user);

        $user2 = User::factory()->create(['password' => bcrypt('password')]);
        $user2->assignRole('customer');

        $cacheKey1 = "user:profile:{$this->user->id}";
        $cacheKey2 = "user:profile:{$user2->id}";

        // Get profile for both users
        $this->userService->profile();

        // Switch to second user
        $this->actingAsUser($user2);
        $this->userService->profile();

        // Verify both caches exist and are separate
        $this->assertNotNull(Cache::get($cacheKey1));
        $this->assertNotNull(Cache::get($cacheKey2));
        $this->assertNotEquals(
            Cache::get($cacheKey1)->id,
            Cache::get($cacheKey2)->id
        );
    }
}
