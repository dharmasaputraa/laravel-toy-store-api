<?php

namespace App\Services;

use App\Models\Region;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class RegionService
{
    /**
     * Waktu simpan cache (30 hari).
     */
    protected int $cacheTtl = 60 * 60 * 24 * 30;

    public function getProvinces(): Collection
    {
        $cacheKey = 'provinces_all';

        return Cache::tags(['regions', 'provinces'])->remember($cacheKey, $this->cacheTtl, function () {
            return Region::provinces()->get();
        });
    }

    public function getCitiesByProvince(string $provinceCode): Collection
    {
        $cacheKey = "cities_{$provinceCode}";

        return Cache::tags(['regions', 'cities'])->remember($cacheKey, $this->cacheTtl, function () use ($provinceCode) {
            return Region::cities()->byParent($provinceCode)->get();
        });
    }

    public function getDistrictsByCity(string $cityCode): Collection
    {
        $cacheKey = "districts_{$cityCode}";

        return Cache::tags(['regions', 'districts'])->remember($cacheKey, $this->cacheTtl, function () use ($cityCode) {
            return Region::districts()->byParent($cityCode)->get();
        });
    }

    public function getVillagesByDistrict(string $districtCode): Collection
    {
        $cacheKey = "villages_{$districtCode}";

        return Cache::tags(['regions', 'villages'])->remember($cacheKey, $this->cacheTtl, function () use ($districtCode) {
            return Region::villages()->byParent($districtCode)->get();
        });
    }
}
