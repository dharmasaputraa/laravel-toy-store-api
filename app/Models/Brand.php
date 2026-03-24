<?php

namespace App\Models;

use App\Traits\HasFlexibleRouteBinding;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Brand extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia, HasFlexibleRouteBinding;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return Cache::tags(['brands', 'logo'])->remember(
            "brand:logo:{$this->id}",
            now()->addDay(),
            fn() => $this->getFirstMediaUrl('logo') ?: null
        );
    }
}
