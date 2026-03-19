<?php

namespace App\DTOs\User\Address;

use Illuminate\Http\Request;

class SaveUserAddressData
{
    public function __construct(
        public string $label,
        public string $recipient_name,
        public string $phone,
        public string $province_id,
        public string $city_id,
        public string $district,
        public string $postal_code,
        public string $full_address,
        public bool $is_default,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            label: $request->input('label'),
            recipient_name: $request->input('recipient_name'),
            phone: $request->input('phone'),
            province_id: $request->input('province_id'),
            city_id: $request->input('city_id'),
            district: $request->input('district'),
            postal_code: $request->input('postal_code'),
            full_address: $request->input('full_address'),
            is_default: $request->boolean('is_default'),
        );
    }

    public function toArray(): array
    {
        return [
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
