<?php

namespace App\Http\Requests\V1\Category;

use Illuminate\Foundation\Http\FormRequest;

class UploadCategoryImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:2048'],
        ];
    }
}
