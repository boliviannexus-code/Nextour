<?php

namespace App\Http\Requests\PublicRegistration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreIndependentRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commercial_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'document_number' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'address' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'work_type' => ['required', 'string', Rule::in(['guide', 'photographer', 'transport', 'operator', 'artisan', 'other'])],
            'is_certified_guide' => ['required', 'boolean'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'id_front' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'id_back' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }
}
