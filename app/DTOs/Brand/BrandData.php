<?php

namespace App\DTOs\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

readonly class BrandData
{
    public function __construct(
        public ?string $name,
        public ?string $slug,
        public ?string $description,
        public ?bool $is_active,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            slug: $request->input('slug'),
            description: $request->input('description'),
            is_active: $request->boolean('is_active'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ], fn($v) => !is_null($v));
    }

    public function toFilteredArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ], fn($value) => !is_null($value));
    }
}
