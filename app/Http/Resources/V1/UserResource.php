<?php

namespace App\Http\Resources\V1;

use App\Enums\RoleType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        $avatar = $this->avatar ? $this->getCachedAvatarUrl($this->avatar, $disk) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $avatar,
            'locale' => $this->locale,
            'email_verified_at' => $this->email_verified_at,
            'is_email_verified' => ! is_null($this->email_verified_at),

            'roles' => $this->roles->map(function ($role) {
                return $this->getCachedRoleData($role);
            }),

            'created_at' => $this->created_at,
        ];
    }

    /**
     * Get cached avatar URL to reduce S3 API calls
     */
    private function getCachedAvatarUrl(string $avatarPath, mixed $disk): string
    {
        $cacheKey = "user:avatar:{$avatarPath}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($avatarPath, $disk) {
            return $disk->url($avatarPath);
        });
    }

    /**
     * Get cached role transformation to reduce enum lookups
     */
    private function getCachedRoleData(mixed $role): array
    {
        $cacheKey = "role:data:{$role->id}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($role) {
            $roleEnum = RoleType::tryFrom($role->name);

            return [
                'value' => $role->name,
                'label' => $roleEnum?->label() ?? 'Tidak diketahui',
            ];
        });
    }
}
