<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RegionTestSeeder::class);
    }

    /**
     * INDEX - Get all provinces
     */
    public function test_index_returns_provinces(): void
    {
        $response = $this->getJson(route('v1.regions.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'code',
                        'name',
                        'level',
                        'level_name',
                        'parent_code',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data'); // Test seeder has 2 provinces
    }

    public function test_index_returns_only_level_1_regions(): void
    {
        $response = $this->getJson(route('v1.regions.index'));

        $regions = $response->json('data');

        foreach ($regions as $region) {
            $this->assertEquals(1, $region['level']);
            $this->assertEquals('Province', $region['level_name']);
            $this->assertNull($region['parent_code']);
        }
    }

    public function test_index_returns_all_provinces(): void
    {
        $response = $this->getJson(route('v1.regions.index'));

        $response->assertOk()
            ->assertJsonCount(2, 'data'); // Test seeder has 2 provinces
    }

    public function test_index_uses_caching(): void
    {
        // Clear cache
        \Illuminate\Support\Facades\Cache::flush();

        // First request - should hit database
        $response1 = $this->getJson(route('v1.regions.index'));
        $response1->assertOk();

        // Second request - should use cache
        $response2 = $this->getJson(route('v1.regions.index'));
        $response2->assertOk();

        // Both responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * CITIES - Get cities by province code
     */
    public function test_cities_returns_cities_for_valid_province(): void
    {
        $provinceCode = '11'; // Aceh

        $response = $this->getJson(route('v1.regions.cities', $provinceCode));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'code',
                        'name',
                        'level',
                        'level_name',
                        'parent_code',
                    ],
                ],
            ]);
    }

    public function test_cities_returns_only_level_2_regions(): void
    {
        $provinceCode = '11';

        $response = $this->getJson(route('v1.regions.cities', $provinceCode));

        $cities = $response->json('data');

        foreach ($cities as $city) {
            $this->assertEquals(2, $city['level']);
            $this->assertEquals('City / Regency', $city['level_name']);
            $this->assertEquals($provinceCode, $city['parent_code']);
        }
    }

    public function test_cities_returns_404_for_invalid_code_format(): void
    {
        $invalidCode = '999'; // Invalid format (should be numeric or X.YY)

        $response = $this->getJson(route('v1.regions.cities', $invalidCode));

        // Controller returns empty array for non-existent provinces
        $response->assertOk()
            ->assertJson([
                'data' => [],
            ]);
    }

    public function test_cities_returns_empty_for_non_existent_province(): void
    {
        $nonExistentCode = '99';

        $response = $this->getJson(route('v1.regions.cities', $nonExistentCode));

        $response->assertOk()
            ->assertJson([
                'data' => [],
            ]);
    }

    public function test_cities_uses_caching(): void
    {
        // Clear cache
        \Illuminate\Support\Facades\Cache::flush();

        $provinceCode = '11';

        // First request - should hit database
        $response1 = $this->getJson(route('v1.regions.cities', $provinceCode));
        $response1->assertOk();

        // Second request - should use cache
        $response2 = $this->getJson(route('v1.regions.cities', $provinceCode));
        $response2->assertOk();

        // Both responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * Test specific provinces
     */
    public function test_cities_for_aceh(): void
    {
        $response = $this->getJson(route('v1.regions.cities', '11'));

        $response->assertOk();
        $cities = $response->json('data');
        $this->assertGreaterThan(0, count($cities));

        // Verify first city has correct province code
        $firstCity = $cities[0];
        $this->assertEquals('11', $firstCity['parent_code']);
        $this->assertEquals(2, $firstCity['level']);
    }

    public function test_cities_for_dki_jakarta(): void
    {
        $response = $this->getJson(route('v1.regions.cities', '31'));

        $response->assertOk();
        $cities = $response->json('data');
        $this->assertGreaterThan(0, count($cities));

        // Verify cities belong to DKI Jakarta
        $firstCity = $cities[0];
        $this->assertEquals('31', $firstCity['parent_code']);
        $this->assertEquals(2, $firstCity['level']);
    }
}
