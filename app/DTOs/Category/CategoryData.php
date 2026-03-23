<?php

namespace App\DTOs\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

readonly class CategoryData
{
    public function __construct(
        public ?string $parent_id,
        public ?string $name,
        public ?string $slug,
        public ?string $description,
        public ?int $sort_order,
        public ?bool $is_active,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            parent_id: $request->input('parent_id'),
            name: $request->input('name'),
            slug: $request->input('slug'),
            description: $request->input('description'),
            sort_order: $request->input('sort_order'),
            is_active: $request->boolean('is_active'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ], fn($v) => !is_null($v));
    }

    public function toFilteredArray(): array
    {
        return array_filter([
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ], fn($value) => !is_null($value));
    }
}
