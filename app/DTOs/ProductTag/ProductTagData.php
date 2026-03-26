<?php

namespace App\DTOs\ProductTag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

readonly class ProductTagData
{
    public function __construct(
        public ?string $id,
        public ?string $name,
        public ?string $slug,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            id: $request->input('id'),
            name: $request->input('name'),
            slug: $request->input('slug'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ], fn($v) => !is_null($v));
    }

    public function toFilteredArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ], fn($value) => !is_null($value));
    }
}
