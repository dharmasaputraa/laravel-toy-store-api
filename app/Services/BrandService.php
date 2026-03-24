<?php

namespace App\Services;

use App\DTOs\Brand\BrandData;
use App\Models\Brand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandService
{
    public function store(BrandData $data): Brand
    {
        return DB::transaction(function () use ($data) {

            $brand = Brand::create($data->toArray());

            $this->clearCache();

            return $brand;
        });
    }

    public function update(Brand $brand, BrandData $data): Brand
    {
        return DB::transaction(function () use ($brand, $data) {

            $brand->update($data->toArray());

            $this->clearCache();

            return $brand->refresh();
        });
    }

    public function updateStatus(Brand $brand, bool $status): Brand
    {
        $brand->update(['is_active' => $status]);

        $this->clearCache();

        return $brand;
    }

    public function updateLogo(Brand $brand, $file): Brand
    {
        $brand
            ->addMedia($file)
            ->usingFileName(
                Str::slug($brand->slug) . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension()
            )
            ->toMediaCollection('logo');

        Cache::tags(['brands', 'logo'])->flush();

        return $brand->refresh();
    }

    public function delete(Brand $brand): void
    {
        DB::transaction(function () use ($brand) {

            $brand->delete();

            $this->clearCache();
        });
    }

    public function getAll()
    {
        return Cache::tags(['brands'])->remember(
            'brands:all',
            now()->addDay(),
            function () {
                return Brand::where('is_active', true)
                    ->orderBy('name')
                    ->get();
            }
        );
    }

    private function clearCache(): void
    {
        Cache::tags(['brands'])->flush();
    }
}
