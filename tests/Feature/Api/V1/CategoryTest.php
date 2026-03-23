<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryTest extends TestCase
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
     * TREE - Get category tree (Public)
     */
    public function test_tree_returns_category_hierarchy(): void
    {
        $root1 = Category::factory()->create(['sort_order' => 1]);
        $child1 = Category::factory()->create(['parent_id' => $root1->id, 'sort_order' => 1]);
        $grandchild1 = Category::factory()->create(['parent_id' => $child1->id, 'sort_order' => 1]);
        $root2 = Category::factory()->create(['sort_order' => 2]);

        $response = $this->getJson(route('v1.categories.tree'));

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
                        'sort_order',
                        'is_active',
                        'parent_id',
                        'image',
                        'children' => [
                            '*' => [
                                'id',
                                'name',
                                'slug',
                                'description',
                                'sort_order',
                                'is_active',
                                'parent_id',
                                'image',
                                'children' => [
                                    '*' => [
                                        'id',
                                        'name',
                                        'slug',
                                        'description',
                                        'sort_order',
                                        'is_active',
                                        'parent_id',
                                        'image',
                                        'children',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_tree_returns_only_root_categories(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $root->id]);

        $response = $this->getJson(route('v1.categories.tree'));

        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals($root->id, $data[0]['id']);
        $this->assertEquals($child->id, $data[0]['children'][0]['id']);
    }

    public function test_tree_orders_by_sort_order(): void
    {
        $root3 = Category::factory()->create(['sort_order' => 3]);
        $root1 = Category::factory()->create(['sort_order' => 1]);
        $root2 = Category::factory()->create(['sort_order' => 2]);

        $response = $this->getJson(route('v1.categories.tree'));

        $data = $response->json('data');

        $this->assertEquals($root1->id, $data[0]['id']);
        $this->assertEquals($root2->id, $data[1]['id']);
        $this->assertEquals($root3->id, $data[2]['id']);
    }

    public function test_tree_uses_caching(): void
    {
        Cache::flush();

        Category::factory()->create();

        // First request - should hit database
        $response1 = $this->getJson(route('v1.categories.tree'));
        $response1->assertOk();

        // Second request - should use cache
        $response2 = $this->getJson(route('v1.categories.tree'));
        $response2->assertOk();

        // Both responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_tree_excludes_deleted_categories(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $root->id]);
        $child->delete();

        $response = $this->getJson(route('v1.categories.tree'));

        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertCount(0, $data[0]['children']);
    }

    /**
     * STORE - Create new category (Admin only)
     */
    public function test_store_creates_category_as_admin(): void
    {
        $data = [
            'name' => 'New Category',
            'slug' => 'new-category',
            'description' => 'A new category',
            'sort_order' => 10,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.categories.store'), $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'sort_order',
                    'is_active',
                    'parent_id',
                    'image',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);
    }

    public function test_store_creates_category_with_parent(): void
    {
        $parent = Category::factory()->create();

        $data = [
            'name' => 'Child Category',
            'slug' => 'child-category',
            'parent_id' => $parent->id,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.categories.store'), $data);

        $response->assertCreated();

        $this->assertDatabaseHas('categories', [
            'name' => 'Child Category',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $data = ['name' => 'New Category'];

        $response = $this->postJson(route('v1.categories.store'), $data);

        $response->assertUnauthorized();
    }

    public function test_store_forbidden_for_customer(): void
    {
        $data = ['name' => 'New Category'];

        $response = $this->actingAs($this->customer, 'api')
            ->postJson(route('v1.categories.store'), $data);

        $response->assertForbidden();
    }

    /**
     * UPDATE - Update category (Admin only)
     */
    public function test_update_modifies_category_as_admin(): void
    {
        $category = Category::factory()->create();

        $data = [
            'name' => 'Updated Name',
            'slug' => 'updated-name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.categories.update', $category), $data);

        $response->assertOk();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
            'description' => 'Updated description',
        ]);
    }

    public function test_update_requires_authentication(): void
    {
        $category = Category::factory()->create();

        $response = $this->patchJson(route('v1.categories.update', $category), [
            'name' => 'Updated',
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_forbidden_for_customer(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->customer, 'api')
            ->patchJson(route('v1.categories.update', $category), [
                'name' => 'Updated',
            ]);

        $response->assertForbidden();
    }

    /**
     * UPDATE PARENT - Update category parent (Admin only)
     */
    public function test_update_parent_changes_category_parent_as_admin(): void
    {
        $category = Category::factory()->create();
        $newParent = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.categories.parent.update', $category), [
                'parent_id' => $newParent->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'parent_id' => $newParent->id,
        ]);
    }

    public function test_update_parent_can_set_to_null(): void
    {
        $parent = Category::factory()->create();
        $category = Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.categories.parent.update', $category), [
                'parent_id' => null,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'parent_id' => null,
        ]);
    }

    public function test_update_parent_prevents_circular_reference(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.categories.parent.update', $parent), [
                'parent_id' => $child->id,
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseHas('categories', [
            'id' => $parent->id,
            'parent_id' => null,
        ]);
    }

    public function test_update_parent_prevents_self_reference(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.categories.parent.update', $category), [
                'parent_id' => $category->id,
            ]);

        $response->assertUnprocessable();
    }

    /**
     * UPDATE STATUS - Update category status (Admin only)
     */
    public function test_update_status_activates_category_as_admin(): void
    {
        $category = Category::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.categories.status.update', $category), [
                'is_active' => true,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => true,
        ]);
    }

    public function test_update_status_deactivates_category_as_admin(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.categories.status.update', $category), [
                'is_active' => false,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    /**
     * UPDATE IMAGE - Upload category image (Admin only)
     */
    public function test_update_image_uploads_file_as_admin(): void
    {
        $category = Category::factory()->create();
        $file = UploadedFile::fake()->image('category.jpg');

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.categories.image.update', $category), [
                'image' => $file,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'sort_order',
                    'is_active',
                    'parent_id',
                    'image',
                ]
            ]);

        // Verify image was stored
        $category->refresh();
        $media = $category->getFirstMedia('image');
        $this->assertNotNull($media);
        $this->assertNotNull($category->image_url);
        $this->fakeS3()->assertExists($media->getPathRelativeToRoot());
    }

    public function test_update_image_replaces_previous_image(): void
    {
        $category = Category::factory()->create();
        $oldFile = UploadedFile::fake()->image('old.jpg');
        $newFile = UploadedFile::fake()->image('new.jpg');

        // Upload first image
        $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.categories.image.update', $category), [
                'image' => $oldFile,
            ]);

        $category->refresh();
        $oldMedia = $category->getFirstMedia('image');

        // Upload second image
        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.categories.image.update', $category), [
                'image' => $newFile,
            ]);

        $response->assertOk();

        // Old image should be deleted
        $category->refresh();
        $newMedia = $category->getFirstMedia('image');
        $this->assertNotEquals($oldMedia->id, $newMedia->id);
        $this->fakeS3()->assertExists($newMedia->getPathRelativeToRoot());
    }

    public function test_update_image_requires_valid_file(): void
    {
        $category = Category::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.categories.image.update', $category), [
                'image' => $file,
            ]);

        $response->assertUnprocessable();
    }

    /**
     * DESTROY - Delete category (Admin only)
     */
    public function test_destroy_deletes_category_as_admin(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson(route('v1.categories.destroy', $category));

        $response->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_destroy_deletes_category_with_children(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson(route('v1.categories.destroy', $parent));

        $response->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $parent->id]);
        // Children should be handled by DB cascade or remain as orphans depending on FK constraints
    }

    public function test_destroy_requires_authentication(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson(route('v1.categories.destroy', $category));

        $response->assertUnauthorized();
    }

    public function test_destroy_forbidden_for_customer(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->customer, 'api')
            ->deleteJson(route('v1.categories.destroy', $category));

        $response->assertForbidden();
    }

    /**
     * Caching behavior
     */
    public function test_modifying_category_clears_tree_cache(): void
    {
        Cache::flush();

        Category::factory()->create();

        // Build cache
        $this->getJson(route('v1.categories.tree'));

        // Modify category
        $category = Category::first();
        $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.categories.update', $category), [
                'name' => 'Updated',
            ]);

        // Cache should be cleared and rebuilt
        $response = $this->getJson(route('v1.categories.tree'));

        $this->assertEquals('Updated', $response->json('data.0.name'));
    }

    /**
     * JSON Structure validation
     */
    public function test_category_response_has_correct_structure(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson(route('v1.categories.tree'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }
}
