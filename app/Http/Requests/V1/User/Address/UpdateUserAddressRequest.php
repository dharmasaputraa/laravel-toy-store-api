<?php

namespace App\Http\Requests\V1\User\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Asumsi nama parameter route adalah {address}
        return $this->user('api')->can('update', $this->route('address'));
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'province_id' => ['required', 'integer'],
            'city_id' => ['required', 'integer'],
            'district' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:10'],
            'full_address' => ['required', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
