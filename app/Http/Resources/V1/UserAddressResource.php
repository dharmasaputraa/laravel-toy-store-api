<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
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
            'label' => $this->label,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'province' => $this->whenLoaded('province', function () {
                return [
                    'id' => $this->province->code,
                    'name' => $this->province->name,
                ];
            }),
            'city' => $this->whenLoaded('city', function () {
                return [
                    'id' => $this->city->code,
                    'name' => $this->city->name,
                ];
            }),
            'district' => $this->district,
            'postal_code' => $this->postal_code,
            'full_address' => $this->full_address,
            'is_default' => $this->is_default,
        ];
    }
}
