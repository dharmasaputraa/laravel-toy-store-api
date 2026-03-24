<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleType;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleType::SUPER_ADMIN->value);

        $this->customer = User::factory()->create();
        $this->customer->assignRole(RoleType::CUSTOMER->value);

        Storage::fake('s3');
    }

    protected function fakeS3(): \Illuminate\Filesystem\FilesystemAdapter
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter */
        $disk = Storage::disk('s3');

        return $disk;
    }

    /**
     * INDEX - Get all brands (Public)
     */
    public function test_index_returns_all_active_brands(): void
    {
        Brand::factory()->create(['name' => 'Brand A', 'is_active' => true]);
        Brand::factory()->create(['name' => 'Brand B', 'is_active' => true]);
        Brand::factory()->create(['name' => 'Brand C', 'is_active' => false]);

        $response = $this->getJson(route('v1.brands.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'logo',
                        'is_active',
                        'created_at',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Brand A', $data[0]['name']);
        $this->assertEquals('Brand B', $data[1]['name']);
    }

    public function test_index_orders_brands_by_name(): void
    {
        Brand::factory()->create(['name' => 'Charlie']);
        Brand::factory()->create(['name' => 'Alpha']);
        Brand::factory()->create(['name' => 'Bravo']);

        $response = $this->getJson(route('v1.brands.index'));

        $data = $response->json('data');

        $this->assertEquals('Alpha', $data[0]['name']);
        $this->assertEquals('Bravo', $data[1]['name']);
        $this->assertEquals('Charlie', $data[2]['name']);
    }

    public function test_index_uses_caching(): void
    {
        Cache::flush();

        Brand::factory()->create();

        // First request - should hit database
        $response1 = $this->getJson(route('v1.brands.index'));
        $response1->assertOk();

        // Second request - should use cache
        $response2 = $this->getJson(route('v1.brands.index'));
        $response2->assertOk();

        // Both responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_index_returns_empty_when_no_brands(): void
    {
        $response = $this->getJson(route('v1.brands.index'));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEmpty($data);
    }

    /**
     * SHOW - Get single brand (Public)
     */
    public function test_show_returns_single_brand(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'description' => 'Test description',
        ]);

        $response = $this->getJson(route('v1.brands.show', $brand));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'logo',
                    'is_active',
                    'created_at',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('Test Brand', $data['name']);
        $this->assertEquals('test-brand', $data['slug']);
        $this->assertEquals('Test description', $data['description']);
    }

    public function test_show_returns_404_for_nonexistent_brand(): void
    {
        $fakeUuid = '12345678-1234-1234-1234-123456789012';

        $response = $this->getJson(route('v1.brands.show', $fakeUuid));

        $response->assertNotFound();
    }

    /**
     * STORE - Create new brand (Admin only)
     */
    public function test_store_creates_brand_as_admin(): void
    {
        $data = [
            'name' => 'New Brand',
            'slug' => 'new-brand',
            'description' => 'A new brand',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.brands.store'), $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'logo',
                    'is_active',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('brands', [
            'name' => 'New Brand',
            'slug' => 'new-brand',
        ]);
    }

    public function test_store_creates_inactive_brand(): void
    {
        $data = [
            'name' => 'Inactive Brand',
            'slug' => 'inactive-brand',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.brands.store'), $data);

        $response->assertCreated();

        $this->assertDatabaseHas('brands', [
            'name' => 'Inactive Brand',
            'is_active' => false,
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $data = ['name' => 'New Brand'];

        $response = $this->postJson(route('v1.brands.store'), $data);

        $response->assertUnauthorized();
    }

    public function test_store_forbidden_for_customer(): void
    {
        $data = ['name' => 'New Brand'];

        $response = $this->actingAs($this->customer, 'api')
            ->postJson(route('v1.brands.store'), $data);

        $response->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.brands.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_store_validates_unique_slug(): void
    {
        Brand::factory()->create(['slug' => 'existing-slug']);

        $data = [
            'name' => 'New Brand',
            'slug' => 'existing-slug',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.brands.store'), $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    /**
     * UPDATE - Update brand (Admin only)
     */
    public function test_update_modifies_brand_as_admin(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-slug',
        ]);

        $data = [
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.brands.update', $brand), $data);

        $response->assertOk();

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'description' => 'Updated description',
        ]);
    }

    public function test_update_allows_partial_update(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.brands.update', $brand), [
                'name' => 'New Name Only',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'New Name Only',
            'description' => 'Original description',
        ]);
    }

    public function test_update_ignores_same_slug(): void
    {
        $brand = Brand::factory()->create(['slug' => 'my-slug']);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.brands.update', $brand), [
                'slug' => 'my-slug',
            ]);

        $response->assertOk();
    }

    public function test_update_prevents_duplicate_slug(): void
    {
        $brand1 = Brand::factory()->create(['slug' => 'slug-1']);
        $brand2 = Brand::factory()->create(['slug' => 'slug-2']);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.brands.update', $brand2), [
                'slug' => 'slug-1',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_update_requires_authentication(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->patchJson(route('v1.brands.update', $brand), [
            'name' => 'Updated',
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_forbidden_for_customer(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->actingAs($this->customer, 'api')
            ->patchJson(route('v1.brands.update', $brand), [
                'name' => 'Updated',
            ]);

        $response->assertForbidden();
    }

    /**
     * UPDATE STATUS - Update brand status (Admin only)
     */
    public function test_update_status_activates_brand_as_admin(): void
    {
        $brand = Brand::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.brands.status.update', $brand), [
                'is_active' => true,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'is_active' => true,
        ]);
    }

    public function test_update_status_deactivates_brand_as_admin(): void
    {
        $brand = Brand::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.brands.status.update', $brand), [
                'is_active' => false,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'is_active' => false,
        ]);
    }

    public function test_update_status_requires_boolean(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.brands.status.update', $brand), [
                'is_active' => 'not-a-boolean',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);
    }

    /**
     * UPDATE LOGO - Upload brand logo (Admin only)
     */
    public function test_update_logo_uploads_file_as_admin(): void
    {
        $brand = Brand::factory()->create();
        $file = UploadedFile::fake()->image('brand-logo.jpg');

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.brands.logo.update', $brand), [
                'logo' => $file,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'logo',
                    'is_active',
                    'created_at',
                ]
            ]);

        // Verify logo was stored
        $brand->refresh();
        $media = $brand->getFirstMedia('logo');
        $this->assertNotNull($media);
        $this->assertNotNull($brand->logo_url);
        $this->fakeS3()->assertExists($media->getPathRelativeToRoot());
    }

    public function test_update_logo_replaces_previous_logo(): void
    {
        $brand = Brand::factory()->create();
        $oldFile = UploadedFile::fake()->image('old.jpg');
        $newFile = UploadedFile::fake()->image('new.jpg');

        // Upload first logo
        $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.brands.logo.update', $brand), [
                'logo' => $oldFile,
            ]);

        $brand->refresh();
        $oldMedia = $brand->getFirstMedia('logo');

        // Upload second logo
        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.brands.logo.update', $brand), [
                'logo' => $newFile,
            ]);

        $response->assertOk();

        // Old logo should be deleted
        $brand->refresh();
        $newMedia = $brand->getFirstMedia('logo');
        $this->assertNotEquals($oldMedia->id, $newMedia->id);
        $this->fakeS3()->assertExists($newMedia->getPathRelativeToRoot());
    }

    public function test_update_logo_requires_valid_file(): void
    {
        $brand = Brand::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.brands.logo.update', $brand), [
                'logo' => $file,
            ]);

        $response->assertUnprocessable();
    }

    public function test_update_logo_requires_file(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.brands.logo.update', $brand), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['logo']);
    }

    public function test_update_logo_validates_file_size(): void
    {
        $brand = Brand::factory()->create();
        $file = UploadedFile::fake()->image('large.jpg')->size(3000); // 3MB > 2MB limit

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.brands.logo.update', $brand), [
                'logo' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['logo']);
    }

    /**
     * DESTROY - Delete brand (Admin only)
     */
    public function test_destroy_deletes_brand_as_admin(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson(route('v1.brands.destroy', $brand));

        $response->assertOk();

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->deleteJson(route('v1.brands.destroy', $brand));

        $response->assertUnauthorized();
    }

    public function test_destroy_forbidden_for_customer(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->actingAs($this->customer, 'api')
            ->deleteJson(route('v1.brands.destroy', $brand));

        $response->assertForbidden();
    }

    public function test_destroy_returns_404_for_nonexistent_brand(): void
    {
        $fakeUuid = '12345678-1234-1234-1234-123456789012';

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson(route('v1.brands.destroy', $fakeUuid));

        $response->assertNotFound();
    }

    /**
     * JSON Structure validation
     */
    public function test_brand_response_has_correct_structure(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->getJson(route('v1.brands.show', $brand));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    /**
     * Route parameter binding
     */
    public function test_route_binding_works_with_slug(): void
    {
        $brand = Brand::factory()->create(['slug' => 'my-brand']);

        $response = $this->getJson(route('v1.brands.show', $brand));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals($brand->id, $data['id']);
    }
}
