<?php

namespace App\Http\Requests\V1\ProductTag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ProductTag::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required'],
            'slug' => ['required', 'unique:product_tags,slug'],
        ];
    }
}
