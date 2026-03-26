<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasUuids, HasFactory;

    protected string $currency = 'IDR';

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'compare_price',
        'stock',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'attributes' => 'array',
        'price' => MoneyCast::class,
        'compare_price' => MoneyCast::class,
    ];

    protected $appends = [
        'price_formatted',
        'compare_price_formatted',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT (AUTO SKU + NORMALIZE)
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        static::creating(function ($variant) {
            // Normalize attributes (biar konsisten)
            $variant->attributes = self::normalizeAttributes($variant->attributes);

            // Auto SKU kalau kosong
            if (!$variant->sku) {
                $variant->sku = self::generateSku($variant);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */
    public function getPriceFormattedAttribute(): string
    {
        return $this->price?->format() ?? '-';
    }

    public function getComparePriceFormattedAttribute(): ?string
    {
        return $this->compare_price?->format();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SKU GENERATOR
    |--------------------------------------------------------------------------
    */
    protected static function generateSku(self $variant): string
    {
        // Get product - load from DB if not already loaded
        $product = $variant->product ?? Product::find($variant->product_id);

        $base = $product
            ? strtoupper(substr($product->slug, 0, 5))
            : 'PRD';

        $attributes = collect($variant->attributes ?? [])
            ->values()
            ->map(fn($v) => strtoupper(Str::slug($v)))
            ->join('-');

        $random = strtoupper(Str::random(4));

        return trim("{$base}-{$attributes}-{$random}", '-');
    }

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTE NORMALIZER
    |--------------------------------------------------------------------------
    */
    protected static function normalizeAttributes(?array $attributes): array
    {
        if (!$attributes) {
            return [];
        }

        ksort($attributes);

        return $attributes;
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
