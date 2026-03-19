<?php

namespace App\Http\Requests\V1\User\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Setiap user yang sudah login (auth:api) boleh membuat alamat
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'province_id' => ['required', 'string', 'max:13', 'exists:regions,code'],
            'city_id' => ['required', 'string', 'max:13', 'exists:regions,code'],
            'district' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:10'],
            'full_address' => ['required', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
