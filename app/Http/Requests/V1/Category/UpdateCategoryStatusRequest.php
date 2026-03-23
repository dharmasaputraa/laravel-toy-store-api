<?php

namespace App\Http\Requests\V1\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
