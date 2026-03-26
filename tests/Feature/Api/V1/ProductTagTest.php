<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleType;
use App\Models\Product;
use App\Models\ProductTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductTagTest extends TestCase
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
    }

    /**
     * INDEX - Get all product tags (Public)
     */
    public function test_index_returns_all_tags_with_products_count(): void
    {
        $tag1 = ProductTag::factory()->withName('Alpha Popular')->create();
        $tag2 = ProductTag::factory()->withName('Beta New Arrival')->create();
        $tag3 = ProductTag::factory()->withName('Gamma Trending')->create();

        // Attach products to tags
        Product::factory()->count(3)->create()->each(function ($product) use ($tag1) {
            $tag1->products()->attach($product->id);
        });

        Product::factory()->count(1)->create()->each(function ($product) use ($tag2) {
            $tag2->products()->attach($product->id);
        });

        $response = $this->getJson(route('v1.tags.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'products_count',
                        'created_at',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
        // Tags are ordered by name, so Alpha has 3 products
        $this->assertEquals('Alpha Popular', $data[0]['name']);
        $this->assertEquals(3, $data[0]['products_count']);
        $this->assertEquals('Beta New Arrival', $data[1]['name']);
        $this->assertEquals(1, $data[1]['products_count']);
        $this->assertEquals('Gamma Trending', $data[2]['name']);
        $this->assertEquals(0, $data[2]['products_count']);
    }

    public function test_index_orders_tags_by_name(): void
    {
        ProductTag::factory()->withName('Zebra')->create();
        ProductTag::factory()->withName('Alpha')->create();
        ProductTag::factory()->withName('Beta')->create();

        $response = $this->getJson(route('v1.tags.index'));

        $data = $response->json('data');

        $this->assertEquals('Alpha', $data[0]['name']);
        $this->assertEquals('Beta', $data[1]['name']);
        $this->assertEquals('Zebra', $data[2]['name']);
    }

    public function test_index_supports_sorting_asc(): void
    {
        ProductTag::factory()->withName('Charlie')->create();
        ProductTag::factory()->withName('Alpha')->create();
        ProductTag::factory()->withName('Beta')->create();

        $response = $this->getJson(route('v1.tags.index', ['sort' => 'name']));

        $data = $response->json('data');

        $this->assertEquals('Alpha', $data[0]['name']);
        $this->assertEquals('Beta', $data[1]['name']);
        $this->assertEquals('Charlie', $data[2]['name']);
    }

    public function test_index_supports_sorting_desc(): void
    {
        ProductTag::factory()->withName('Alpha')->create();
        ProductTag::factory()->withName('Beta')->create();
        ProductTag::factory()->withName('Charlie')->create();

        $response = $this->getJson(route('v1.tags.index', ['sort' => '-name']));

        $data = $response->json('data');

        $this->assertEquals('Charlie', $data[0]['name']);
        $this->assertEquals('Beta', $data[1]['name']);
        $this->assertEquals('Alpha', $data[2]['name']);
    }

    public function test_index_supports_multiple_sort_fields(): void
    {
        $tag1 = ProductTag::factory()->withName('Test Tag')->create();
        $tag2 = ProductTag::factory()->withName('Test Tag Two')->create();

        $response = $this->getJson(route('v1.tags.index', ['sort' => 'name,-created_at']));

        $data = $response->json('data');

        $this->assertEquals('Test Tag', $data[0]['name']);
        $this->assertEquals('Test Tag Two', $data[1]['name']);
    }

    public function test_index_sorts_by_products_count_desc(): void
    {
        $tag1 = ProductTag::factory()->withName('Most Popular')->create();
        $tag2 = ProductTag::factory()->withName('Less Popular')->create();

        Product::factory()->count(5)->create()->each(function ($product) use ($tag1) {
            $tag1->products()->attach($product->id);
        });

        Product::factory()->count(2)->create()->each(function ($product) use ($tag2) {
            $tag2->products()->attach($product->id);
        });

        $response = $this->getJson(route('v1.tags.index', ['sort' => '-products_count']));

        $data = $response->json('data');

        $this->assertEquals(5, $data[0]['products_count']);
        $this->assertEquals(2, $data[1]['products_count']);
    }

    public function test_index_uses_caching(): void
    {
        Cache::flush();

        ProductTag::factory()->count(3)->create();

        // First request - should hit database
        $response1 = $this->getJson(route('v1.tags.index'));
        $response1->assertOk();

        // Second request - should use cache
        $response2 = $this->getJson(route('v1.tags.index'));
        $response2->assertOk();

        // Both responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_index_returns_empty_when_no_tags(): void
    {
        $response = $this->getJson(route('v1.tags.index'));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEmpty($data);
    }

    public function test_index_does_not_include_products_by_default(): void
    {
        $tag = ProductTag::factory()->create();
        Product::factory()->count(3)->create()->each(function ($product) use ($tag) {
            $tag->products()->attach($product->id);
        });

        $response = $this->getJson(route('v1.tags.index'));

        $data = $response->json('data');

        $this->assertArrayNotHasKey('products', $data[0]);
    }

    /**
     * SHOW - Get single product tag (Public)
     */
    public function test_show_returns_single_tag(): void
    {
        $tag = ProductTag::factory()->withName('Best Seller')->create();

        $response = $this->getJson(route('v1.tags.show', $tag));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'products_count',
                    'created_at',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('Best Seller', $data['name']);
        $this->assertEquals('best-seller', $data['slug']);
    }

    public function test_show_returns_404_for_nonexistent_tag(): void
    {
        $fakeUuid = '12345678-1234-1234-1234-123456789012';

        $response = $this->getJson(route('v1.tags.show', $fakeUuid));

        $response->assertNotFound();
    }

    public function test_show_includes_products_with_default_limit(): void
    {
        $tag = ProductTag::factory()->create();
        Product::factory()->count(10)->create()->each(function ($product) use ($tag) {
            $tag->products()->attach($product->id);
        });

        $response = $this->getJson(route('v1.tags.show', $tag) . '?include=products');

        $data = $response->json('data');

        $this->assertArrayHasKey('products', $data);
        $this->assertCount(5, $data['products']); // Default limit is 5
    }

    public function test_show_includes_products_with_custom_limit(): void
    {
        $tag = ProductTag::factory()->create();
        Product::factory()->count(10)->create()->each(function ($product) use ($tag) {
            $tag->products()->attach($product->id);
        });

        $response = $this->getJson(route('v1.tags.show', $tag) . '?include=products&products_limit=8');

        $data = $response->json('data');

        $this->assertCount(8, $data['products']);
    }

    public function test_show_limits_products_to_maximum(): void
    {
        $tag = ProductTag::factory()->create();
        Product::factory()->count(30)->create()->each(function ($product) use ($tag) {
            $tag->products()->attach($product->id);
        });

        $response = $this->getJson(route('v1.tags.show', $tag) . '?include=products&products_limit=100');

        $data = $response->json('data');

        $this->assertCount(20, $data['products']); // Max limit is 20
    }

    public function test_show_includes_products_with_nested_category(): void
    {
        $tag = ProductTag::factory()->create();
        $product = Product::factory()->create();
        $tag->products()->attach($product->id);

        $response = $this->getJson(route('v1.tags.show', $tag) . '?include=products,products.category');

        $data = $response->json('data');

        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('category', $data['products'][0]);
    }

    public function test_show_includes_products_with_nested_brand(): void
    {
        $tag = ProductTag::factory()->create();
        $product = Product::factory()->create();
        $tag->products()->attach($product->id);

        $response = $this->getJson(route('v1.tags.show', $tag) . '?include=products,products.brand');

        $data = $response->json('data');

        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('brand', $data['products'][0]);
    }

    public function test_show_does_not_include_products_when_not_requested(): void
    {
        $tag = ProductTag::factory()->create();
        Product::factory()->count(3)->create()->each(function ($product) use ($tag) {
            $tag->products()->attach($product->id);
        });

        $response = $this->getJson(route('v1.tags.show', $tag));

        $data = $response->json('data');

        $this->assertArrayNotHasKey('products', $data);
    }

    public function test_show_products_are_limited_to_latest(): void
    {
        $tag = ProductTag::factory()->create();
        $products = Product::factory()->count(5)->create();
        $tag->products()->attach($products);

        sleep(1);
        $newestProduct = Product::factory()->create();
        $tag->products()->attach($newestProduct->id);

        $response = $this->getJson(route('v1.tags.show', $tag) . '?include=products');

        $data = $response->json('data');

        $this->assertCount(5, $data['products']);
        $this->assertEquals($newestProduct->id, $data['products'][0]['id']);
    }

    /**
     * GET PRODUCTS - Get paginated products for a tag (Public)
     */
    public function test_products_returns_paginated_products_for_tag(): void
    {
        $tag = ProductTag::factory()->create();
        Product::factory()->count(15)->create()->each(function ($product) use ($tag) {
            $tag->products()->attach($product->id);
        });

        $response = $this->getJson(route('v1.tags.products', $tag));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'sku',
                            'short_description',
                            'category',
                            'brand',
                        ],
                    ],
                    'links',
                    'meta',
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(15, $data['data']);
    }

    public function test_products_supports_pagination(): void
    {
        $tag = ProductTag::factory()->create();
        Product::factory()->count(25)->create()->each(function ($product) use ($tag) {
            $tag->products()->attach($product->id);
        });

        $response = $this->getJson(route('v1.tags.products', $tag) . '?per_page=10');

        $data = $response->json('data');

        $this->assertCount(10, $data['data']);
        $this->assertEquals(25, $data['meta']['total']);
        $this->assertEquals(3, $data['meta']['last_page']);
    }

    public function test_products_includes_category_and_brand_by_default(): void
    {
        $tag = ProductTag::factory()->create();
        $product = Product::factory()->create();
        $tag->products()->attach($product->id);

        $response = $this->getJson(route('v1.tags.products', $tag));

        $data = $response->json('data');

        $this->assertArrayHasKey('category', $data['data'][0]);
        $this->assertArrayHasKey('brand', $data['data'][0]);
    }

    public function test_products_can_include_tags(): void
    {
        $tag = ProductTag::factory()->create();
        $product = Product::factory()->create();
        $tag->products()->attach($product->id);

        $response = $this->getJson(route('v1.tags.products', $tag) . '?include=tags');

        $data = $response->json('data');

        $this->assertArrayHasKey('tags', $data['data'][0]);
    }

    public function test_products_can_include_variants(): void
    {
        $tag = ProductTag::factory()->create();
        $product = Product::factory()->create();
        \App\Models\ProductVariant::factory()->count(2)->create(['product_id' => $product->id]);
        $tag->products()->attach($product->id);

        $response = $this->getJson(route('v1.tags.products', $tag) . '?include=variants');

        $data = $response->json('data');

        $this->assertArrayHasKey('variants', $data['data'][0]);
        $this->assertCount(2, $data['data'][0]['variants']);
    }

    public function test_products_supports_sorting(): void
    {
        $tag = ProductTag::factory()->create();
        Product::factory()->create(['name' => 'Zebra Product']);
        Product::factory()->create(['name' => 'Alpha Product']);
        Product::factory()->create(['name' => 'Beta Product']);

        foreach (Product::all() as $product) {
            $tag->products()->attach($product->id);
        }

        $response = $this->getJson(route('v1.tags.products', $tag) . '?sort=name');

        $data = $response->json('data');

        $this->assertEquals('Alpha Product', $data['data'][0]['name']);
        $this->assertEquals('Beta Product', $data['data'][1]['name']);
        $this->assertEquals('Zebra Product', $data['data'][2]['name']);
    }

    public function test_products_returns_empty_when_no_products_for_tag(): void
    {
        $tag = ProductTag::factory()->create();

        $response = $this->getJson(route('v1.tags.products', $tag));

        $response->assertOk();

        $data = $response->json('data');

        // Check that data key exists and is empty
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('links', $data);
        $this->assertEmpty($data['data']);
        $this->assertEquals(0, $data['meta']['total']);
    }

    /**
     * STORE - Create new product tag (Admin only)
     */
    public function test_store_creates_tag_as_admin(): void
    {
        $data = [
            'name' => 'New Tag',
            'slug' => 'new-tag',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.tags.store'), $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'products_count',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('product_tags', [
            'name' => 'New Tag',
            'slug' => 'new-tag',
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $data = ['name' => 'New Tag'];

        $response = $this->postJson(route('v1.tags.store'), $data);

        $response->assertUnauthorized();
    }

    public function test_store_forbidden_for_customer(): void
    {
        $data = ['name' => 'New Tag'];

        $response = $this->actingAs($this->customer, 'api')
            ->postJson(route('v1.tags.store'), $data);

        $response->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.tags.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_store_validates_unique_slug(): void
    {
        ProductTag::factory()->create(['name' => 'Existing Tag', 'slug' => 'existing-slug']);

        $data = [
            'name' => 'New Tag',
            'slug' => 'existing-slug',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson(route('v1.tags.store'), $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    /**
     * UPDATE - Update product tag (Admin only)
     */
    public function test_update_modifies_tag_as_admin(): void
    {
        $tag = ProductTag::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-slug',
        ]);

        $data = [
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.tags.update', $tag), $data);

        $response->assertOk();

        $tag->refresh();
        $this->assertEquals('Updated Name', $tag->name);
        // Slug is kept as provided
        $this->assertEquals('updated-slug', $tag->slug);
    }

    public function test_update_allows_partial_update(): void
    {
        $tag = ProductTag::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-slug',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.tags.update', $tag), [
                'name' => 'New Name Only',
            ]);

        $response->assertOk();

        $tag->refresh();
        $this->assertEquals('New Name Only', $tag->name);
        // Slug is kept as original when not provided
        $this->assertEquals('original-slug', $tag->slug);
    }

    public function test_update_ignores_same_slug(): void
    {
        $tag = ProductTag::factory()->create(['slug' => 'my-slug']);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.tags.update', $tag), [
                'slug' => 'my-slug',
            ]);

        $response->assertOk();
    }

    public function test_update_prevents_duplicate_slug(): void
    {
        $tag1 = ProductTag::factory()->create(['slug' => 'slug-1']);
        $tag2 = ProductTag::factory()->create(['slug' => 'slug-2']);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson(route('v1.tags.update', $tag2), [
                'slug' => 'slug-1',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_update_requires_authentication(): void
    {
        $tag = ProductTag::factory()->create();

        $response = $this->patchJson(route('v1.tags.update', $tag), [
            'name' => 'Updated',
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_forbidden_for_customer(): void
    {
        $tag = ProductTag::factory()->create();

        $response = $this->actingAs($this->customer, 'api')
            ->patchJson(route('v1.tags.update', $tag), [
                'name' => 'Updated',
            ]);

        $response->assertForbidden();
    }

    /**
     * DESTROY - Delete product tag (Admin only)
     */
    public function test_destroy_deletes_tag_as_admin(): void
    {
        $tag = ProductTag::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson(route('v1.tags.destroy', $tag));

        $response->assertOk();

        $this->assertDatabaseMissing('product_tags', ['id' => $tag->id]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $tag = ProductTag::factory()->create();

        $response = $this->deleteJson(route('v1.tags.destroy', $tag));

        $response->assertUnauthorized();
    }

    public function test_destroy_forbidden_for_customer(): void
    {
        $tag = ProductTag::factory()->create();

        $response = $this->actingAs($this->customer, 'api')
            ->deleteJson(route('v1.tags.destroy', $tag));

        $response->assertForbidden();
    }

    public function test_destroy_returns_404_for_nonexistent_tag(): void
    {
        $fakeUuid = '12345678-1234-1234-1234-123456789012';

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson(route('v1.tags.destroy', $fakeUuid));

        $response->assertNotFound();
    }

    public function test_destroy_clears_cache(): void
    {
        Cache::shouldReceive('tags->flush')->once();

        $tag = ProductTag::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->deleteJson(route('v1.tags.destroy', $tag));
    }

    /**
     * Route parameter binding
     */
    public function test_route_binding_works_with_slug(): void
    {
        $tag = ProductTag::factory()->create(['slug' => 'my-tag']);

        $response = $this->getJson(route('v1.tags.show', $tag));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals($tag->id, $data['id']);
    }

    /**
     * JSON Structure validation
     */
    public function test_tag_response_has_correct_structure(): void
    {
        $tag = ProductTag::factory()->create();

        $response = $this->getJson(route('v1.tags.show', $tag));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    /**
     * Products count handling
     */
    public function test_products_count_defaults_to_zero_when_no_products(): void
    {
        $tag = ProductTag::factory()->create();

        $response = $this->getJson(route('v1.tags.show', $tag));

        $data = $response->json('data');
        $this->assertEquals(0, $data['products_count']);
    }
}
