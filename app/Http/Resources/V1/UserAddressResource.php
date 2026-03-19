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
            'district' => $this->district,
            'postal_code' => $this->postal_code,
            'full_address' => $this->full_address,
            'is_default' => $this->is_default,
        ];
    }
}
