<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function getImageUrlAttribute(): ?string
    {
        return Cache::tags(['categories', 'image'])->remember(
            "category:image:{$this->id}",
            now()->addDay(),
            fn() => $this->getFirstMediaUrl('image') ?: null
        );
    }
}
