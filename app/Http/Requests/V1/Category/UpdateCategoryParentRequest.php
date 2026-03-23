<?php

namespace App\Http\Requests\V1\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryParentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'exists:categories,id'],
        ];
    }
}
