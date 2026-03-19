<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserAddress;
use App\Services\UserAddressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserAddressServiceCachingTest extends TestCase
{
    use RefreshDatabase;

    private UserAddressService $addressService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addressService = app(UserAddressService::class);
        $this->user = User::factory()->create();
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

    public function test_get_all_caches_addresses(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:addresses:{$this->user->id}";

        // Create test addresses
        UserAddress::factory()->count(3)->create(['user_id' => $this->user->id]);

        // Ensure cache is empty
        $this->assertNull(Cache::get($cacheKey));

        // First call should cache data
        $result1 = $this->addressService->getAll($this->user);

        // Verify cache is populated
        $this->assertNotNull(Cache::get($cacheKey));
        $cachedData = Cache::get($cacheKey);
        $this->assertCount(3, $cachedData);

        // Second call should return cached data
        $result2 = $this->addressService->getAll($this->user);

        // Both results should be same
        $this->assertEquals($result1->count(), $result2->count());
        $this->assertEquals(3, $result2->count());
    }

    public function test_get_all_cache_has_60_minutes_ttl(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:addresses:{$this->user->id}";

        UserAddress::factory()->count(2)->create(['user_id' => $this->user->id]);

        // Make the call
        $this->addressService->getAll($this->user);

        // Check that cache exists
        $this->assertNotNull(Cache::get($cacheKey));
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_store_invalidates_cache(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:addresses:{$this->user->id}";

        // Populate cache
        UserAddress::factory()->count(2)->create(['user_id' => $this->user->id]);
        $this->addressService->getAll($this->user);
        $this->assertNotNull(Cache::get($cacheKey));

        // Store new address
        $addressData = new \App\DTOs\User\Address\SaveUserAddressData(
            label: 'Home',
            recipient_name: 'John Doe',
            phone: '08123456789',
            province_id: 1,
            city_id: 1,
            district: 'Central Jakarta',
            postal_code: '12345',
            full_address: '123 Main St',
            is_default: false,
        );

        $this->addressService->store($this->user, $addressData);

        // Verify cache is invalidated
        $this->assertNull(Cache::get($cacheKey));

        // Verify address was created
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'recipient_name' => 'John Doe',
        ]);
    }

    public function test_update_invalidates_cache(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:addresses:{$this->user->id}";

        // Populate cache
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);
        $this->addressService->getAll($this->user);
        $this->assertNotNull(Cache::get($cacheKey));

        // Update address
        $addressData = new \App\DTOs\User\Address\SaveUserAddressData(
            label: 'Office',
            recipient_name: 'Jane Doe',
            phone: '08987654321',
            province_id: 2,
            city_id: 2,
            district: 'South Jakarta',
            postal_code: '54321',
            full_address: '456 Business Ave',
            is_default: false,
        );

        $this->addressService->update($this->user, $address, $addressData);

        // Verify cache is invalidated
        $this->assertNull(Cache::get($cacheKey));

        // Verify address was updated
        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'recipient_name' => 'Jane Doe',
        ]);
    }

    public function test_delete_invalidates_cache(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:addresses:{$this->user->id}";

        // Populate cache
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);
        $this->addressService->getAll($this->user);
        $this->assertNotNull(Cache::get($cacheKey));

        // Delete address
        $this->addressService->delete($this->user, $address);

        // Verify cache is invalidated
        $this->assertNull(Cache::get($cacheKey));

        // Verify address was deleted
        $this->assertDatabaseMissing('user_addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_cache_key_format_is_correct(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:addresses:{$this->user->id}";

        UserAddress::factory()->count(1)->create(['user_id' => $this->user->id]);
        $this->addressService->getAll($this->user);

        // Verify exact cache key format
        $this->assertNotNull(Cache::get($cacheKey));
        $this->assertStringStartsWith('user:addresses:', $cacheKey);
        $this->assertStringEndsWith((string)$this->user->id, $cacheKey);
    }

    public function test_multiple_users_have_separate_cache_entries(): void
    {
        $this->actingAsUser($this->user);

        $user2 = User::factory()->create();

        $cacheKey1 = "user:addresses:{$this->user->id}";
        $cacheKey2 = "user:addresses:{$user2->id}";

        // Create addresses for both users
        UserAddress::factory()->count(2)->create(['user_id' => $this->user->id]);
        UserAddress::factory()->count(3)->create(['user_id' => $user2->id]);

        // Get addresses for first user
        $this->addressService->getAll($this->user);

        // Switch to second user
        $this->actingAsUser($user2);
        $this->addressService->getAll($user2);

        // Verify both caches exist and are separate
        $this->assertNotNull(Cache::get($cacheKey1));
        $this->assertNotNull(Cache::get($cacheKey2));
        $this->assertNotEquals(
            Cache::get($cacheKey1)->count(),
            Cache::get($cacheKey2)->count()
        );
    }

    public function test_addresses_are_ordered_correctly_in_cache(): void
    {
        $this->actingAsUser($this->user);

        $cacheKey = "user:addresses:{$this->user->id}";

        // Create addresses with different default status
        $address1 = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);
        $address2 = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        $addresses = $this->addressService->getAll($this->user);

        // Verify default address comes first
        $this->assertTrue($addresses->first()->is_default);
        $this->assertFalse($addresses->last()->is_default);

        // Verify cache preserves ordering
        $cachedAddresses = Cache::get($cacheKey);
        $this->assertTrue($cachedAddresses->first()->is_default);
    }
}
