<?php

namespace App\Http\Requests\V1\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');
        return $this->user()->can('update', $category);
    }

    public function rules(): array
    {
        $id = $this->route('category')->id;

        return [
            'name' => ['sometimes'],
            'slug' => ["sometimes", "unique:categories,slug,$id"],
            'description' => ['nullable'],
            'sort_order' => ['integer'],
        ];
    }
}
