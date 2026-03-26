<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\ProductTag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\UnitTestCase;

class ProductTagTest extends UnitTestCase
{
    use RefreshDatabase;

    /**
     * Model Basics
     */
    public function test_uses_uuids(): void
    {
        $tag = ProductTag::factory()->create();

        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $tag->id);
    }

    public function test_fillable_attributes(): void
    {
        $tag = ProductTag::factory()->create([
            'name' => 'New Arrival',
            'slug' => 'new-arrival',
        ]);

        $this->assertEquals('New Arrival', $tag->name);
        $this->assertEquals('new-arrival', $tag->slug);
    }

    /**
     * Relationships
     */
    public function test_has_products_relationship(): void
    {
        $tag = ProductTag::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $tag->products()->attach([$product1->id, $product2->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $tag->products());
        $this->assertCount(2, $tag->products);
        $this->assertTrue($tag->products->contains('id', $product1->id));
        $this->assertTrue($tag->products->contains('id', $product2->id));
    }

    public function test_can_attach_single_product(): void
    {
        $tag = ProductTag::factory()->create();
        $product = Product::factory()->create();

        $tag->products()->attach($product->id);

        $this->assertCount(1, $tag->products);
        $this->assertEquals($product->id, $tag->products->first()->id);
    }

    public function test_can_detach_product(): void
    {
        $tag = ProductTag::factory()->create();
        $product = Product::factory()->create();

        $tag->products()->attach($product->id);
        $this->assertCount(1, $tag->products);

        $tag->products()->detach($product->id);
        $tag->refresh();

        $this->assertCount(0, $tag->products);
    }

    public function test_can_sync_products(): void
    {
        $tag = ProductTag::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        // Attach first two
        $tag->products()->sync([$product1->id, $product2->id]);
        $this->assertCount(2, $tag->products);

        // Sync to replace with different set
        $tag->products()->sync([$product2->id, $product3->id]);
        $tag->refresh();

        $this->assertCount(2, $tag->products);
        $this->assertFalse($tag->products->contains('id', $product1->id));
        $this->assertTrue($tag->products->contains('id', $product2->id));
        $this->assertTrue($tag->products->contains('id', $product3->id));
    }

    /**
     * Factory
     */
    public function test_factory_creates_valid_tag(): void
    {
        $tag = ProductTag::factory()->create();

        $this->assertDatabaseHas('product_tags', [
            'id' => $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug,
        ]);
    }

    public function test_factory_can_create_tag_with_custom_name(): void
    {
        $tag = ProductTag::factory()->withName('Best Seller')->create();

        $this->assertEquals('Best Seller', $tag->name);
        $this->assertEquals('best-seller', $tag->slug);
    }

    public function test_slug_is_auto_generated_from_name(): void
    {
        $tag = ProductTag::factory()->create(['name' => 'Limited Edition', 'slug' => '']);

        $this->assertEquals('limited-edition', $tag->slug);
    }

    public function test_factory_creates_multiple_tags(): void
    {
        ProductTag::factory()->count(5)->create();

        $this->assertDatabaseCount('product_tags', 5);
    }

    /**
     * Slug Behavior
     */
    public function test_slug_is_stored_correctly(): void
    {
        $tag = ProductTag::factory()->create([
            'name' => 'Limited Edition',
            'slug' => 'limited-edition',
        ]);

        $this->assertEquals('limited-edition', $tag->slug);
    }

    /**
     * Products Count
     */
    public function test_can_count_products(): void
    {
        $tag = ProductTag::factory()->create();
        Product::factory()->count(3)->create()->each(function ($product) use ($tag) {
            $tag->products()->attach($product->id);
        });

        $tagWithCount = ProductTag::withCount('products')->find($tag->id);

        $this->assertEquals(3, $tagWithCount->products_count);
    }

    public function test_products_count_is_zero_when_no_products(): void
    {
        $tag = ProductTag::factory()->create();

        $tagWithCount = ProductTag::withCount('products')->find($tag->id);

        $this->assertEquals(0, $tagWithCount->products_count);
    }

    /**
     * Business Logic
     */
    public function test_multiple_products_can_have_same_tag(): void
    {
        $tag = ProductTag::factory()->create();
        $products = Product::factory()->count(5)->create();

        foreach ($products as $product) {
            $tag->products()->attach($product->id);
        }

        $this->assertCount(5, $tag->products);
        foreach ($products as $product) {
            $this->assertTrue($tag->products->contains('id', $product->id));
        }
    }

    public function test_product_can_have_multiple_tags(): void
    {
        $product = Product::factory()->create();
        $tag1 = ProductTag::factory()->withName('Tag One')->create();
        $tag2 = ProductTag::factory()->withName('Tag Two')->create();
        $tag3 = ProductTag::factory()->withName('Tag Three')->create();

        $product->tags()->attach([$tag1->id, $tag2->id, $tag3->id]);

        $this->assertCount(3, $product->tags);
    }

    public function test_pivot_table_is_created_correctly(): void
    {
        $tag = ProductTag::factory()->create();
        $product = Product::factory()->create();

        $tag->products()->attach($product->id);

        $this->assertDatabaseHas('product_tag', [
            'product_id' => $product->id,
            'product_tag_id' => $tag->id,
        ]);
    }

    public function test_pivot_table_is_deleted_on_detach(): void
    {
        $tag = ProductTag::factory()->create();
        $product = Product::factory()->create();

        $tag->products()->attach($product->id);

        $this->assertDatabaseHas('product_tag', [
            'product_id' => $product->id,
            'product_tag_id' => $tag->id,
        ]);

        $tag->products()->detach($product->id);

        $this->assertDatabaseMissing('product_tag', [
            'product_id' => $product->id,
            'product_tag_id' => $tag->id,
        ]);
    }

    /**
     * Query Scopes
     */
    public function test_can_filter_tags_by_name(): void
    {
        ProductTag::factory()->withName('Popular')->create();
        ProductTag::factory()->withName('Trending')->create();
        ProductTag::factory()->withName('New Arrival')->create();

        $popularTags = ProductTag::where('name', 'Popular')->get();

        $this->assertCount(1, $popularTags);
        $this->assertEquals('Popular', $popularTags->first()->name);
    }

    public function test_can_order_tags_by_name(): void
    {
        ProductTag::factory()->withName('Zebra')->create();
        ProductTag::factory()->withName('Alpha')->create();
        ProductTag::factory()->withName('Beta')->create();

        $tags = ProductTag::orderBy('name')->get();

        $this->assertEquals('Alpha', $tags[0]->name);
        $this->assertEquals('Beta', $tags[1]->name);
        $this->assertEquals('Zebra', $tags[2]->name);
    }

    public function test_can_get_tags_with_products_count_ordered(): void
    {
        $tag1 = ProductTag::factory()->withName('Most Popular')->create();
        $tag2 = ProductTag::factory()->withName('Less Popular')->create();

        Product::factory()->count(5)->create()->each(function ($product) use ($tag1) {
            $tag1->products()->attach($product->id);
        });

        Product::factory()->count(2)->create()->each(function ($product) use ($tag2) {
            $tag2->products()->attach($product->id);
        });

        $tags = ProductTag::withCount('products')
            ->orderBy('products_count', 'desc')
            ->get();

        $this->assertEquals(5, $tags[0]->products_count);
        $this->assertEquals(2, $tags[1]->products_count);
    }

    /**
     * Timestamps
     */
    public function test_timestamps_are_stored(): void
    {
        $tag = ProductTag::factory()->create();

        $this->assertNotNull($tag->created_at);
        $this->assertNotNull($tag->updated_at);
    }

    public function test_updated_at_changes_on_update(): void
    {
        $tag = ProductTag::factory()->create();
        $originalUpdatedAt = $tag->updated_at;

        sleep(1);
        $tag->update(['name' => 'Updated Tag']);

        $this->assertGreaterThan($originalUpdatedAt, $tag->updated_at);
    }
}
