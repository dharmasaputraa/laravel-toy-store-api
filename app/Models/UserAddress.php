<?php

namespace App\Models;

use App\Enums\RoleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class UserAddress extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'province_id',
        'city_id',
        'district',
        'postal_code',
        'full_address',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOwnedOrAdmin($query)
    {
        $user = Auth::user();

        if (!$user?->isSuperAdmin()) {
            $query->where('user_id', $user?->id);
        }

        return $query;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->ownedOrAdmin()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
