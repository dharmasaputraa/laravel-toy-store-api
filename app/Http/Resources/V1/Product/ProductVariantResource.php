<?php

namespace App\Http\Resources\V1\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'price' => $this->price?->getAmount(),
            'price_formatted' => $this->price_formatted,
            'compare_price' => $this->compare_price?->getAmount(),
            'compare_price_formatted' => $this->compare_price_formatted,
            'stock' => $this->stock,
            'attributes' => $this->attributes,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
