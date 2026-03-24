<?php

namespace Tests\Unit\Models;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Unit\UnitTestCase;

class BrandTest extends UnitTestCase
{
    use RefreshDatabase;

    /**
     * Model Basics
     */
    public function test_uses_uuids(): void
    {
        $brand = Brand::factory()->create();

        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $brand->id);
    }

    public function test_fillable_attributes(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $this->assertEquals('Test Brand', $brand->name);
        $this->assertEquals('test-brand', $brand->slug);
        $this->assertEquals('Test description', $brand->description);
        $this->assertTrue($brand->is_active);
    }

    /**
     * Media Library (Spatie)
     */
    public function test_logo_can_be_added_to_media_collection(): void
    {
        $brand = Brand::factory()->create();

        $this->assertInstanceOf(\Spatie\MediaLibrary\MediaCollections\Models\Media::class, $brand->media()->getRelated());
    }

    /**
     * Caching
     */
    public function test_logo_url_is_cached(): void
    {
        Cache::flush();

        $brand = Brand::factory()->create();

        // First access - should cache result
        $logoUrl1 = $brand->logo_url;

        // Second access - should use cache
        $logoUrl2 = $brand->logo_url;

        $this->assertSame($logoUrl1, $logoUrl2);
    }

    public function test_logo_url_returns_null_when_no_logo(): void
    {
        $brand = Brand::factory()->create();

        $this->assertNull($brand->logo_url);
    }

    /**
     * Scopes and Queries
     */
    public function test_can_get_active_brands(): void
    {
        Brand::factory()->create(['is_active' => true]);
        Brand::factory()->create(['is_active' => true]);
        Brand::factory()->create(['is_active' => false]);

        $activeBrands = Brand::where('is_active', true)->get();

        $this->assertCount(2, $activeBrands);
    }

    public function test_brands_are_ordered_by_name(): void
    {
        $brandC = Brand::factory()->create(['name' => 'Charlie']);
        $brandA = Brand::factory()->create(['name' => 'Alpha']);
        $brandB = Brand::factory()->create(['name' => 'Bravo']);

        $brands = Brand::orderBy('name')->get();

        $this->assertEquals($brandA->id, $brands[0]->id);
        $this->assertEquals($brandB->id, $brands[1]->id);
        $this->assertEquals($brandC->id, $brands[2]->id);
    }

    /**
     * Attribute Casting
     */
    public function test_is_active_is_cast_to_bool(): void
    {
        $brand = Brand::factory()->create(['is_active' => true]);

        $this->assertIsBool($brand->is_active);
        $this->assertTrue($brand->is_active);
    }

    public function test_is_active_false_casts_to_bool(): void
    {
        $brand = Brand::factory()->create(['is_active' => false]);

        $this->assertIsBool($brand->is_active);
        $this->assertFalse($brand->is_active);
    }

    /**
     * Slug Behavior
     */
    public function test_slug_is_stored_correctly(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'LEGO',
            'slug' => 'lego',
        ]);

        $this->assertEquals('lego', $brand->slug);
    }

    public function test_slug_can_contain_hyphens(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Mattel Inc',
            'slug' => 'mattel-inc',
        ]);

        $this->assertEquals('mattel-inc', $brand->slug);
    }

    /**
     * Description
     */
    public function test_description_can_be_null(): void
    {
        $brand = Brand::factory()->create(['description' => null]);

        $this->assertNull($brand->description);
    }

    public function test_description_can_be_long_text(): void
    {
        $longDescription = str_repeat('This is a brand description. ', 100);

        $brand = Brand::factory()->create(['description' => $longDescription]);

        $this->assertEquals($longDescription, $brand->description);
    }

    public function test_description_can_be_short_text(): void
    {
        $brand = Brand::factory()->create(['description' => 'Short desc']);

        $this->assertEquals('Short desc', $brand->description);
    }

    /**
     * Logo Field
     */
    public function test_logo_can_be_null(): void
    {
        $brand = Brand::factory()->create(['logo' => null]);

        $this->assertNull($brand->logo);
    }

    public function test_logo_can_be_string(): void
    {
        $brand = Brand::factory()->create(['logo' => 'brand-logo.jpg']);

        $this->assertEquals('brand-logo.jpg', $brand->logo);
    }

    /**
     * Brand Name
     */
    public function test_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Brand::factory()->create(['name' => null]);
    }

    public function test_name_can_be_long(): void
    {
        $longName = str_repeat('Brand Name ', 50);

        $brand = Brand::factory()->create(['name' => $longName]);

        $this->assertEquals($longName, $brand->name);
    }

    /**
     * Slug Uniqueness
     */
    public function test_slug_must_be_unique(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Brand::factory()->create(['slug' => 'unique-slug']);
        Brand::factory()->create(['slug' => 'unique-slug']);
    }

    /**
     * Active Status
     */
    public function test_default_active_status_is_true(): void
    {
        $brand = Brand::factory()->raw();

        $this->assertTrue($brand['is_active']);
    }

    public function test_can_deactivate_brand(): void
    {
        $brand = Brand::factory()->create(['is_active' => true]);
        $brand->update(['is_active' => false]);

        $brand->refresh();

        $this->assertFalse($brand->is_active);
    }

    /**
     * Timestamps
     */
    public function test_has_created_at_timestamp(): void
    {
        $brand = Brand::factory()->create();

        $this->assertNotNull($brand->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $brand->created_at);
    }

    public function test_has_updated_at_timestamp(): void
    {
        $brand = Brand::factory()->create();

        $this->assertNotNull($brand->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $brand->updated_at);
    }
}
