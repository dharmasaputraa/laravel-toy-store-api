<?php

namespace Tests\Feature\Api\V1\UserAddress;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Api\V1\Auth\AuthTestCase;

class UserAddressTest extends AuthTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRegions();
        $this->user = $this->createUser([
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Helper method to get valid region codes for testing
     */
    protected function getValidProvinceCode(): string
    {
        return '11'; // Aceh
    }

    protected function getValidCityCode(): string
    {
        return '11.01'; // Kab. Aceh Besar
    }

    protected function getValidProvinceCode2(): string
    {
        return '31'; // DKI Jakarta
    }

    protected function getValidCityCode2(): string
    {
        return '31.73'; // Kota Administrasi Jakarta Barat
    }

    /**
     * INDEX - Get all addresses
     */
    public function test_index_requires_authentication(): void
    {
        $this->getJson(route('v1.user.addresses.index'))
            ->assertUnauthorized();
    }

    public function test_index_requires_verification(): void
    {
        $unverifiedUser = $this->createUser([
            'email_verified_at' => null,
        ]);

        $this->actingAsUser($unverifiedUser)
            ->getJson(route('v1.user.addresses.index'))
            ->assertForbidden();
    }

    public function test_index_returns_empty_list(): void
    {
        $response = $this->actingAsUser($this->user)
            ->getJson(route('v1.user.addresses.index'));

        $response->assertOk()
            ->assertJson([
                'data' => [],
            ]);
    }

    public function test_index_returns_users_addresses_only(): void
    {
        $otherUser = $this->createUser([
            'email_verified_at' => now(),
        ]);

        // Create addresses for authenticated user
        $address1 = UserAddress::factory()->create(['user_id' => $this->user->id]);
        $address2 = UserAddress::factory()->create(['user_id' => $this->user->id]);

        // Create address for other user
        $otherAddress = UserAddress::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAsUser($this->user)
            ->getJson(route('v1.user.addresses.index'));

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        // Check that both addresses are present (order may vary)
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($address1->id, $ids);
        $this->assertContains($address2->id, $ids);

        // Ensure other user's address is not included
        $this->assertNotContains($otherAddress->id, $ids);
    }

    public function test_index_orders_by_is_default_desc_then_latest(): void
    {
        // Create non-default addresses
        $address1 = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        $address2 = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        // Create default address
        $defaultAddress = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);

        $response = $this->actingAsUser($this->user)
            ->getJson(route('v1.user.addresses.index'));

        $response->assertOk()
            ->assertJsonPath('data.0.id', $defaultAddress->id)
            ->assertJsonPath('data.0.is_default', true);
    }

    /**
     * STORE - Create new address
     */
    public function test_store_requires_authentication(): void
    {
        $this->postJson(route('v1.user.addresses.store'), [])
            ->assertUnauthorized();
    }

    public function test_store_requires_verification(): void
    {
        $unverifiedUser = $this->createUser([
            'email_verified_at' => null,
        ]);

        $this->actingAsUser($unverifiedUser)
            ->postJson(route('v1.user.addresses.store'), [])
            ->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAsUser($this->user)
            ->postJson(route('v1.user.addresses.store'), []);

        $this->assertValidationError($response, [
            'label',
            'recipient_name',
            'phone',
            'province_id',
            'city_id',
            'district',
            'postal_code',
            'full_address',
        ]);
    }

    public function test_store_validates_label_max_length(): void
    {
        $response = $this->actingAsUser($this->user)
            ->postJson(route('v1.user.addresses.store'), [
                'label' => str_repeat('a', 101),
                'recipient_name' => 'John Doe',
                'phone' => '08123456789',
                'province_id' => $this->getValidProvinceCode(),
                'city_id' => $this->getValidCityCode(),
                'district' => 'Test',
                'postal_code' => '12345',
                'full_address' => 'Test Address',
            ]);

        $this->assertValidationError($response, ['label']);
    }

    public function test_store_validates_recipient_name_max_length(): void
    {
        $response = $this->actingAsUser($this->user)
            ->postJson(route('v1.user.addresses.store'), [
                'label' => 'Home',
                'recipient_name' => str_repeat('a', 256),
                'phone' => '08123456789',
                'province_id' => $this->getValidProvinceCode(),
                'city_id' => $this->getValidCityCode(),
                'district' => 'Test',
                'postal_code' => '12345',
                'full_address' => 'Test Address',
            ]);

        $this->assertValidationError($response, ['recipient_name']);
    }

    public function test_store_validates_phone_max_length(): void
    {
        $response = $this->actingAsUser($this->user)
            ->postJson(route('v1.user.addresses.store'), [
                'label' => 'Home',
                'recipient_name' => 'John Doe',
                'phone' => str_repeat('1', 21),
                'province_id' => $this->getValidProvinceCode(),
                'city_id' => $this->getValidCityCode(),
                'district' => 'Test',
                'postal_code' => '12345',
                'full_address' => 'Test Address',
            ]);

        $this->assertValidationError($response, ['phone']);
    }

    public function test_store_validates_province_id_exists(): void
    {
        $response = $this->actingAsUser($this->user)
            ->postJson(route('v1.user.addresses.store'), [
                'label' => 'Home',
                'recipient_name' => 'John Doe',
                'phone' => '08123456789',
                'province_id' => '99.99',
                'city_id' => $this->getValidCityCode(),
                'district' => 'Test',
                'postal_code' => '12345',
                'full_address' => 'Test Address',
            ]);

        $this->assertValidationError($response, ['province_id']);
    }

    public function test_store_validates_city_id_exists(): void
    {
        $response = $this->actingAsUser($this->user)
            ->postJson(route('v1.user.addresses.store'), [
                'label' => 'Home',
                'recipient_name' => 'John Doe',
                'phone' => '08123456789',
                'province_id' => $this->getValidProvinceCode(),
                'city_id' => '99.99.99',
                'district' => 'Test',
                'postal_code' => '12345',
                'full_address' => 'Test Address',
            ]);

        $this->assertValidationError($response, ['city_id']);
    }

    public function test_store_validates_district_max_length(): void
    {
        $response = $this->actingAsUser($this->user)
            ->postJson(route('v1.user.addresses.store'), [
                'label' => 'Home',
                'recipient_name' => 'John Doe',
                'phone' => '08123456789',
                'province_id' => $this->getValidProvinceCode(),
                'city_id' => $this->getValidCityCode(),
                'district' => str_repeat('a', 256),
                'postal_code' => '12345',
                'full_address' => 'Test Address',
            ]);

        $this->assertValidationError($response, ['district']);
    }

    public function test_store_validates_postal_code_max_length(): void
    {
        $response = $this->actingAsUser($this->user)
            ->postJson(route('v1.user.addresses.store'), [
                'label' => 'Home',
                'recipient_name' => 'John Doe',
                'phone' => '08123456789',
                'province_id' => $this->getValidProvinceCode(),
                'city_id' => $this->getValidCityCode(),
                'district' => 'Test',
                'postal_code' => str_repeat('1', 11),
                'full_address' => 'Test Address',
            ]);

        $this->assertValidationError($response, ['postal_code']);
    }

    public function test_store_creates_address_successfully(): void
    {
        $data = [
            'label' => 'Home',
            'recipient_name' => 'John Doe',
            'phone' => '08123456789',
            'province_id' => $this->getValidProvinceCode(),
            'city_id' => $this->getValidCityCode(),
            'district' => 'Central District',
            'postal_code' => '12345',
            'full_address' => '123 Main Street',
            'is_default' => false,
        ];

        $response = $this->actingAsUser($this->user)
            ->postJson(route('v1.user.addresses.store'), $data);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'label' => 'Home',
                    'recipient_name' => 'John Doe',
                    'phone' => '08123456789',
                    'province_id' => $this->getValidProvinceCode(),
                    'city_id' => $this->getValidCityCode(),
                    'district' => 'Central District',
                    'postal_code' => '12345',
                    'full_address' => '123 Main Street',
                    'is_default' => false,
                ],
            ]);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'label' => 'Home',
        ]);
    }

    public function test_store_with_default_sets_other_addresses_to_non_default(): void
    {
        // Create existing default address
        $existingDefault = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);

        // Create new default address
        $response = $this->actingAsUser($this->user)
            ->postJson(route('v1.user.addresses.store'), [
                'label' => 'Office',
                'recipient_name' => 'Jane Doe',
                'phone' => '08987654321',
                'province_id' => $this->getValidProvinceCode2(),
                'city_id' => $this->getValidCityCode2(),
                'district' => 'Business District',
                'postal_code' => '67890',
                'full_address' => '456 Business Ave',
                'is_default' => true,
            ]);

        $response->assertOk();

        // Refresh from database
        $existingDefault->refresh();

        $this->assertFalse($existingDefault->is_default);
        $this->assertDatabaseHas('user_addresses', [
            'id' => $existingDefault->id,
            'is_default' => false,
        ]);
    }

    /**
     * UPDATE - Update existing address
     */
    public function test_update_requires_authentication(): void
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $this->putJson(route('v1.user.addresses.update', $address), [])
            ->assertUnauthorized();
    }

    public function test_update_requires_verification(): void
    {
        $unverifiedUser = $this->createUser([
            'email_verified_at' => null,
        ]);

        $address = UserAddress::factory()->create(['user_id' => $unverifiedUser->id]);

        $this->actingAsUser($unverifiedUser)
            ->putJson(route('v1.user.addresses.update', $address), [])
            ->assertForbidden();
    }

    public function test_update_requires_authorization(): void
    {
        $otherUser = $this->createUser([
            'email_verified_at' => now(),
        ]);

        $otherAddress = UserAddress::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAsUser($this->user)
            ->putJson(route('v1.user.addresses.update', $otherAddress), [
                'label' => 'Updated',
            ])
            ->assertNotFound();
    }

    public function test_update_validates_required_fields(): void
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAsUser($this->user)
            ->putJson(route('v1.user.addresses.update', $address), []);

        $this->assertValidationError($response, [
            'label',
            'recipient_name',
            'phone',
            'province_id',
            'city_id',
            'district',
            'postal_code',
            'full_address',
        ]);
    }

    public function test_update_updates_address_successfully(): void
    {
        $address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'label' => 'Home',
            'recipient_name' => 'John Doe',
        ]);

        $data = [
            'label' => 'Office',
            'recipient_name' => 'Jane Doe',
            'phone' => '08987654321',
            'province_id' => $this->getValidProvinceCode2(),
            'city_id' => $this->getValidCityCode2(),
            'district' => 'Business District',
            'postal_code' => '67890',
            'full_address' => '456 Business Ave',
            'is_default' => false,
        ];

        $response = $this->actingAsUser($this->user)
            ->putJson(route('v1.user.addresses.update', $address), $data);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $address->id,
                    'label' => 'Office',
                    'recipient_name' => 'Jane Doe',
                ],
            ]);

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'label' => 'Office',
            'recipient_name' => 'Jane Doe',
        ]);
    }

    public function test_update_to_default_sets_other_addresses_to_non_default(): void
    {
        // Create existing default address
        $existingDefault = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);

        // Create non-default address to update
        $address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        // Update to default
        $response = $this->actingAsUser($this->user)
            ->putJson(route('v1.user.addresses.update', $address), [
                'label' => $address->label,
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'province_id' => $address->province_id,
                'city_id' => $address->city_id,
                'district' => $address->district,
                'postal_code' => $address->postal_code,
                'full_address' => $address->full_address,
                'is_default' => true,
            ]);

        $response->assertOk();

        // Refresh from database
        $existingDefault->refresh();
        $address->refresh();

        $this->assertFalse($existingDefault->is_default);
        $this->assertTrue($address->is_default);
    }

    public function test_update_non_default_to_non_default_doesnt_affect_other_defaults(): void
    {
        // Create existing default address
        $existingDefault = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);

        // Create non-default address to update
        $address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        // Update without changing default status
        $response = $this->actingAsUser($this->user)
            ->putJson(route('v1.user.addresses.update', $address), [
                'label' => 'Updated Label',
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'province_id' => $address->province_id,
                'city_id' => $address->city_id,
                'district' => $address->district,
                'postal_code' => $address->postal_code,
                'full_address' => $address->full_address,
                'is_default' => false,
            ]);

        $response->assertOk();

        // Refresh from database
        $existingDefault->refresh();

        $this->assertTrue($existingDefault->is_default);
    }

    /**
     * DESTROY - Delete address
     */
    public function test_destroy_requires_authentication(): void
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $this->deleteJson(route('v1.user.addresses.destroy', $address))
            ->assertUnauthorized();
    }

    public function test_destroy_requires_verification(): void
    {
        $unverifiedUser = $this->createUser([
            'email_verified_at' => null,
        ]);

        $address = UserAddress::factory()->create(['user_id' => $unverifiedUser->id]);

        $this->actingAsUser($unverifiedUser)
            ->deleteJson(route('v1.user.addresses.destroy', $address))
            ->assertForbidden();
    }

    public function test_destroy_requires_authorization(): void
    {
        $otherUser = $this->createUser([
            'email_verified_at' => now(),
        ]);

        $otherAddress = UserAddress::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAsUser($this->user)
            ->deleteJson(route('v1.user.addresses.destroy', $otherAddress))
            ->assertNotFound();
    }

    public function test_destroy_deletes_address_successfully(): void
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAsUser($this->user)
            ->deleteJson(route('v1.user.addresses.destroy', $address));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'message' => 'Address deleted successfully.',
                ],
            ]);

        $this->assertDatabaseMissing('user_addresses', [
            'id' => $address->id,
        ]);
    }
}
