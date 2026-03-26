<?php

namespace App\DTOs\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

readonly class ProductData
{
    public function __construct(
        public ?string $id,
        public ?string $name,
        public ?string $slug,
        public ?string $sku,
        public ?string $description,
        public ?string $short_description,
        public ?string $category_id,
        public ?string $brand_id,
        public ?bool $is_active,
        public ?bool $is_featured,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            id: $request->input('id'),
            name: $request->input('name'),
            slug: $request->input('slug'),
            sku: $request->input('sku'),
            description: $request->input('description'),
            short_description: $request->input('short_description'),
            category_id: $request->input('category_id'),
            brand_id: $request->input('brand_id'),
            is_active: $request->boolean('is_active'),
            is_featured: $request->boolean('is_featured'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
        ], fn($v) => !is_null($v));
    }

    public function toFilteredArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
        ], fn($value) => !is_null($value));
    }
}
