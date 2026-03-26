<?php

namespace App\Http\Resources\V1\ProductTag;

use App\Http\Resources\V1\Product\ProductListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductTagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'products_count' => $this->products_count ?? 0,
            'created_at' => $this->created_at,
            'products' => $this->whenLoaded('products', function () {
                return ProductListResource::collection($this->products);
            }),
        ];
    }
}
