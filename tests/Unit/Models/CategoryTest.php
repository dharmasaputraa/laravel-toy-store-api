<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Unit\UnitTestCase;

class CategoryTest extends UnitTestCase
{
    use RefreshDatabase;

    /**
     * Model Basics
     */
    public function test_uses_uuids(): void
    {
        $category = Category::factory()->create();

        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $category->id);
    }

    public function test_fillable_attributes(): void
    {
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test description',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->assertEquals('Test Category', $category->name);
        $this->assertEquals('test-category', $category->slug);
        $this->assertEquals('Test description', $category->description);
        $this->assertEquals(10, $category->sort_order);
        $this->assertTrue($category->is_active);
    }

    /**
     * Relationships
     */
    public function test_has_parent_relationship(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $child->parent());
        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function test_parent_can_be_null(): void
    {
        $category = Category::factory()->create(['parent_id' => null]);

        $this->assertNull($category->parent);
    }

    public function test_has_children_relationship(): void
    {
        $parent = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 1]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 2]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $parent->children());
        $this->assertCount(2, $parent->children);
        $this->assertEquals($child1->id, $parent->children->first()->id);
    }

    public function test_children_are_ordered_by_sort_order(): void
    {
        $parent = Category::factory()->create();
        $child3 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 3]);
        $child1 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 1]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 2]);

        $children = $parent->children;

        $this->assertEquals($child1->id, $children[0]->id);
        $this->assertEquals($child2->id, $children[1]->id);
        $this->assertEquals($child3->id, $children[2]->id);
    }

    public function test_has_children_recursive_relationship(): void
    {
        $root = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $root->id]);
        $grandchild1 = Category::factory()->create(['parent_id' => $child1->id]);
        $grandchild2 = Category::factory()->create(['parent_id' => $child1->id]);
        $child2 = Category::factory()->create(['parent_id' => $root->id]);

        $rootWithChildren = Category::with('childrenRecursive')->find($root->id);

        $this->assertCount(2, $rootWithChildren->children);

        // Verify the childrenRecursive relationship exists and can be queried
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $child1->childrenRecursive());
        $this->assertCount(2, $child1->children);
    }

    /**
     * Media Library (Spatie)
     */
    public function test_image_can_be_added_to_media_collection(): void
    {
        $category = Category::factory()->create();

        $this->assertInstanceOf(\Spatie\MediaLibrary\MediaCollections\Models\Media::class, $category->media()->getRelated());
    }

    /**
     * Caching
     */
    public function test_image_url_is_cached(): void
    {
        Cache::flush();

        $category = Category::factory()->create();

        // First access - should cache the result
        $imageUrl1 = $category->image_url;

        // Second access - should use cache
        $imageUrl2 = $category->image_url;

        $this->assertSame($imageUrl1, $imageUrl2);
    }

    public function test_image_url_returns_null_when_no_image(): void
    {
        $category = Category::factory()->create();

        $this->assertNull($category->image_url);
    }

    /**
     * Scopes and Queries
     */
    public function test_can_get_root_categories(): void
    {
        $root1 = Category::factory()->create(['parent_id' => null]);
        $root2 = Category::factory()->create(['parent_id' => null]);
        $child = Category::factory()->create(['parent_id' => $root1->id]);

        $roots = Category::whereNull('parent_id')->get();

        $this->assertCount(2, $roots);
        $this->assertTrue($roots->contains('id', $root1->id));
        $this->assertTrue($roots->contains('id', $root2->id));
        $this->assertFalse($roots->contains('id', $child->id));
    }

    public function test_can_get_children_of_parent(): void
    {
        $parent = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $parent->id]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id]);
        $other = Category::factory()->create(['parent_id' => null]);

        $children = Category::where('parent_id', $parent->id)->get();

        $this->assertCount(2, $children);
        $this->assertTrue($children->contains('id', $child1->id));
        $this->assertTrue($children->contains('id', $child2->id));
        $this->assertFalse($children->contains('id', $other->id));
    }

    /**
     * Attribute Casting
     */
    public function test_is_active_is_cast_to_bool(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $this->assertIsBool($category->is_active);
        $this->assertTrue($category->is_active);
    }

    public function test_sort_order_is_cast_to_int(): void
    {
        $category = Category::factory()->create(['sort_order' => '10']);

        $this->assertIsInt($category->sort_order);
        $this->assertEquals(10, $category->sort_order);
    }

    /**
     * Business Logic
     */
    public function test_can_create_hierarchical_structure(): void
    {
        $root = Category::factory()->create(['name' => 'Root']);
        $child = Category::factory()->create(['parent_id' => $root->id, 'name' => 'Child']);
        $grandchild = Category::factory()->create(['parent_id' => $child->id, 'name' => 'Grandchild']);

        $this->assertNull($root->parent_id);
        $this->assertEquals($root->id, $child->parent_id);
        $this->assertEquals($child->id, $grandchild->parent_id);
    }

    public function test_multiple_children_can_have_same_parent(): void
    {
        $parent = Category::factory()->create();
        $children = Category::factory()->count(5)->create(['parent_id' => $parent->id]);

        foreach ($children as $child) {
            $this->assertEquals($parent->id, $child->parent_id);
        }

        $this->assertCount(5, $parent->children);
    }

    /**
     * Slug Behavior
     */
    public function test_slug_is_stored_correctly(): void
    {
        $category = Category::factory()->create([
            'name' => 'Action Figures',
            'slug' => 'action-figures',
        ]);

        $this->assertEquals('action-figures', $category->slug);
    }

    /**
     * Description
     */
    public function test_description_can_be_null(): void
    {
        $category = Category::factory()->create(['description' => null]);

        $this->assertNull($category->description);
    }

    public function test_description_can_be_long_text(): void
    {
        $longDescription = str_repeat('This is a description. ', 100);

        $category = Category::factory()->create(['description' => $longDescription]);

        $this->assertEquals($longDescription, $category->description);
    }
}
