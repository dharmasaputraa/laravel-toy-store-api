<?php

namespace App\Http\Resources\V1;

use App\Enums\RoleType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'locale' => $this->locale,

            'roles' => $this->roles->map(function ($role) {
                $roleEnum = RoleType::tryFrom($role->name);

                return [
                    'value' => $role->name,
                    'label' => $roleEnum?->label() ?? 'Tidak diketahui',
                ];
            }),

            'created_at' => $this->created_at,
        ];
    }
}
