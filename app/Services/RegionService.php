<?php

namespace App\Services;

use App\Models\Region;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class RegionService
{
    /**
     * Get all provinces with caching.
     *
     * @return Collection<int, Region>
     */
    public function getProvinces(): Collection
    {
        $cacheKey = 'regions:provinces';

        return Cache::remember($cacheKey, now()->addHour(), function () {
            return Region::provinces()
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get cities by province code with caching.
     *
     * @param string $provinceCode
     * @return Collection<int, Region>
     */
    public function getCitiesByProvince(string $provinceCode): Collection
    {
        $cacheKey = "regions:cities:{$provinceCode}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($provinceCode) {
            return Region::cities()
                ->where('parent_code', $provinceCode)
                ->orderBy('name')
                ->get();
        });
    }
}
