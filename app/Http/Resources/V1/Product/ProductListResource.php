<?php

namespace App\Http\Resources\V1\Product;

use App\Http\Resources\V1\Category\CategoryResource;
use App\Http\Resources\V1\Brand\BrandResource;
use App\Http\Resources\V1\ProductTag\ProductTagResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at,

            // Nested includes - conditional loading
            'category' => $this->whenLoaded('category', function () {
                return new CategoryResource($this->category);
            }),
            'brand' => $this->whenLoaded('brand', function () {
                return new BrandResource($this->brand);
            }),
            'tags' => ProductTagResource::collection(
                $this->whenLoaded('tags')
            ),
            // No variants, no description (lightweight)
        ];
    }
}
