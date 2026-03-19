<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'level',
        'parent_code',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_code', 'code');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_code', 'code');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES (QUERY HELPERS)
    |--------------------------------------------------------------------------
    */

    public function scopeProvinces(Builder $query): Builder
    {
        return $query->where('level', 1);
    }

    public function scopeCities(Builder $query): Builder
    {
        return $query->where('level', 2);
    }

    public function scopeDistricts(Builder $query): Builder
    {
        return $query->where('level', 3);
    }

    public function scopeVillages(Builder $query): Builder
    {
        return $query->where('level', 4);
    }

    public function scopeByParent(Builder $query, ?string $parentCode): Builder
    {
        return $query->where('parent_code', $parentCode);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getLevelNameAttribute(): string
    {
        return match ($this->level) {
            1 => 'Province',
            2 => 'City / Regency',
            3 => 'District',
            4 => 'Village',
            default => 'Unknown',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC HELPERS (FOR SEEDER / UTIL)
    |--------------------------------------------------------------------------
    */

    public static function getLevelFromCode(string $code): int
    {
        return substr_count($code, '.') + 1;
    }

    public static function getParentCode(string $code): ?string
    {
        if (!str_contains($code, '.')) {
            return null;
        }

        return substr($code, 0, strrpos($code, '.'));
    }

    /*
    |--------------------------------------------------------------------------
    | TREE HELPERS
    |--------------------------------------------------------------------------
    */

    // Recursive eager loading (max depth 4)
    public function scopeWithTree(Builder $query): Builder
    {
        return $query->with([
            'children.children.children.children'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | API HELPERS
    |--------------------------------------------------------------------------
    */

    public static function getByCode(string $code): ?self
    {
        return self::find($code);
    }

    public static function getChildrenOf(?string $parentCode)
    {
        return self::byParent($parentCode)->get();
    }
}
