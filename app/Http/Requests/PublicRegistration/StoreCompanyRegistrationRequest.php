<?php

namespace App\Http\Requests\PublicRegistration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreCompanyRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'website' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'legal_representative_first_name' => ['required', 'string', 'max:120'],
            'legal_representative_last_name' => ['required', 'string', 'max:120'],
            'legal_representative_document_number' => ['required', 'string', 'max:80'],
            'legal_representative_document_type' => ['required', 'string', 'max:80'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'tax_document' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }
}
