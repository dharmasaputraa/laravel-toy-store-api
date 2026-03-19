<?php

namespace App\DTOs\User\Profile;

use Illuminate\Http\Request;

class UpdateProfileData
{
    public function __construct(
        public string $name,
        public ?string $phone,
        public ?string $locale,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            phone: $data['phone'] ?? null,
            locale: $data['locale'] ?? null,
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            phone: $request->input('phone'),
            locale: $request->input('locale'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'phone' => $this->phone,
            'locale' => $this->locale,
        ], fn($value) => !is_null($value));
    }
}
