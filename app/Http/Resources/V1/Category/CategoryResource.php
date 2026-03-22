<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),

            // Akan me-render children secara rekursif jika relasi di-load
            'children' => CategoryResource::collection($this->whenLoaded('childrenRecursive')),

            // Atau jika hanya me-load children 1 level
            // 'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
