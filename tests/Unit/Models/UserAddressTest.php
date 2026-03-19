<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\UnitTestCase;

class UserAddressTest extends UnitTestCase
{
    use RefreshDatabase;

    /**
     * Fillable Fields
     */
    public function test_fillable_fields(): void
    {
        $address = new UserAddress();

        $this->assertEquals([
            'user_id',
            'label',
            'recipient_name',
            'phone',
            'province_id',
            'city_id',
            'district',
            'postal_code',
            'full_address',
            'is_default',
        ], $address->getFillable());
    }

    public function test_can_fill_all_fields(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::create([
            'user_id' => $user->id,
            'label' => 'Home',
            'recipient_name' => 'John Doe',
            'phone' => '08123456789',
            'province_id' => 1,
            'city_id' => 1,
            'district' => 'Central District',
            'postal_code' => '12345',
            'full_address' => '123 Main Street',
            'is_default' => true,
        ]);

        $this->assertSame('Home', $address->label);
        $this->assertSame('John Doe', $address->recipient_name);
        $this->assertSame('08123456789', $address->phone);
        $this->assertSame(1, $address->province_id);
        $this->assertSame(1, $address->city_id);
        $this->assertSame('Central District', $address->district);
        $this->assertSame('12345', $address->postal_code);
        $this->assertSame('123 Main Street', $address->full_address);
        $this->assertTrue($address->is_default);
    }

    /**
     * Casts
     */
    public function test_is_default_is_cast_to_boolean(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
            'is_default' => 1, // integer 1 should be cast to true
        ]);

        $this->assertIsBool($address->is_default);
        $this->assertTrue($address->is_default);
    }

    public function test_is_default_can_be_false(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
            'is_default' => 0,
        ]);

        $this->assertIsBool($address->is_default);
        $this->assertFalse($address->is_default);
    }

    /**
     * Relationships
     */
    public function test_belongs_to_user(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $address->user);
        $this->assertSame($user->id, $address->user->id);
    }

    public function test_user_relationship_returns_correct_instance(): void
    {
        $user = $this->createUserPersisted();

        $address = new UserAddress();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $address->user()
        );
    }

    /**
     * Factory
     */
    public function test_factory_creates_valid_address(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($address->label);
        $this->assertNotNull($address->recipient_name);
        $this->assertNotNull($address->phone);
        $this->assertNotNull($address->province_id);
        $this->assertNotNull($address->city_id);
        $this->assertNotNull($address->district);
        $this->assertNotNull($address->postal_code);
        $this->assertNotNull($address->full_address);
    }

    public function test_factory_with_make_does_not_persist(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->make([
            'user_id' => $user->id,
        ]);

        $this->assertNull($address->id);
        $this->assertDatabaseMissing('user_addresses', [
            'label' => $address->label,
        ]);
    }

    /**
     * Mass Assignment
     */
    public function test_mass_assignment_works(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::create([
            'user_id' => $user->id,
            'label' => 'Office',
            'recipient_name' => 'Jane Smith',
            'phone' => '08987654321',
            'province_id' => 2,
            'city_id' => 2,
            'district' => 'Business District',
            'postal_code' => '67890',
            'full_address' => '456 Business Ave',
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('user_addresses', [
            'label' => 'Office',
            'recipient_name' => 'Jane Smith',
        ]);
    }

    /**
     * Array Serialization
     */
    public function test_visible_in_array(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
            'label' => 'Test Label',
        ]);

        $array = $address->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('recipient_name', $array);
        $this->assertArrayHasKey('phone', $array);
        $this->assertArrayHasKey('province_id', $array);
        $this->assertArrayHasKey('city_id', $array);
        $this->assertArrayHasKey('district', $array);
        $this->assertArrayHasKey('postal_code', $array);
        $this->assertArrayHasKey('full_address', $array);
        $this->assertArrayHasKey('is_default', $array);
    }

    /**
     * Timestamps
     */
    public function test_uses_timestamps(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($address->created_at);
        $this->assertNotNull($address->updated_at);
    }

    /**
     * Query Scopes (if any)
     */
    public function test_can_query_by_user(): void
    {
        $user1 = $this->createUserPersisted();
        $user2 = $this->createUserPersisted();

        $address1 = UserAddress::factory()->create(['user_id' => $user1->id]);
        $address2 = UserAddress::factory()->create(['user_id' => $user1->id]);
        $address3 = UserAddress::factory()->create(['user_id' => $user2->id]);

        $user1Addresses = UserAddress::where('user_id', $user1->id)->get();

        $this->assertCount(2, $user1Addresses);
        $this->assertTrue($user1Addresses->contains($address1));
        $this->assertTrue($user1Addresses->contains($address2));
        $this->assertFalse($user1Addresses->contains($address3));
    }

    /**
     * Validation Constraints
     */
    public function test_label_max_length(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
            'label' => str_repeat('a', 100), // max 100
        ]);

        $this->assertDatabaseHas('user_addresses', ['id' => $address->id]);
    }

    public function test_recipient_name_max_length(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
            'recipient_name' => str_repeat('a', 255), // max 255
        ]);

        $this->assertDatabaseHas('user_addresses', ['id' => $address->id]);
    }

    public function test_phone_max_length(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
            'phone' => str_repeat('1', 20), // max 20
        ]);

        $this->assertDatabaseHas('user_addresses', ['id' => $address->id]);
    }

    public function test_district_max_length(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
            'district' => str_repeat('a', 255), // max 255
        ]);

        $this->assertDatabaseHas('user_addresses', ['id' => $address->id]);
    }

    public function test_postal_code_max_length(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
            'postal_code' => str_repeat('1', 10), // max 10
        ]);

        $this->assertDatabaseHas('user_addresses', ['id' => $address->id]);
    }

    /**
     * Update Operations
     */
    public function test_can_update_address(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
            'label' => 'Home',
        ]);

        $address->update([
            'label' => 'Office',
            'recipient_name' => 'Updated Name',
        ]);

        $this->assertSame('Office', $address->label);
        $this->assertSame('Updated Name', $address->recipient_name);
        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'label' => 'Office',
            'recipient_name' => 'Updated Name',
        ]);
    }

    /**
     * Delete Operations
     */
    public function test_can_delete_address(): void
    {
        $user = $this->createUserPersisted();

        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
        ]);

        $address->delete();

        $this->assertDatabaseMissing('user_addresses', [
            'id' => $address->id,
        ]);
    }
}
