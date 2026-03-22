<?php

namespace App\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class CategoryDTO
{
    public function __construct(
        public string $name,
        public ?string $parentId = null,
        public ?string $description = null,
        public ?string $image = null,
        public int $sortOrder = 0,
        public bool $isActive = true,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            parentId: $request->validated('parent_id'),
            description: $request->validated('description'),
            image: $request->validated('image'), // Biasanya berupa path dari hasil upload
            sortOrder: $request->validated('sort_order', 0),
            isActive: $request->validated('is_active', true),
        );
    }
}
