<?php

namespace App\Http\Requests\V1\User\Address;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')->can('delete', $this->route('address'));
    }

    public function rules(): array
    {
        return [];
    }
}
