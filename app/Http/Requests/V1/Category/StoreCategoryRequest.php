<?php


namespace App\Http\Requests\V1\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Category::class);
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required'],
            'slug' => ['required', 'unique:categories,slug'],
            'description' => ['nullable'],
            'sort_order' => ['integer'],
            'is_active' => ['boolean'],
        ];
    }
}
