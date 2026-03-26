<?php

namespace App\Http\Requests\V1\ProductTag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $productTag = $this->route('productTag');
        return $this->user()->can('update', $productTag);
    }

    public function rules(): array
    {
        $id = $this->route('productTag')->id;

        return [
            'name' => ['sometimes'],
            'slug' => ["sometimes", "unique:product_tags,slug,$id"],
        ];
    }
}
