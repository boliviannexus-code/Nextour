<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('companies.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'legal_representative_first_name' => ['nullable', 'string', 'max:120'],
            'legal_representative_last_name' => ['nullable', 'string', 'max:120'],
            'legal_representative_document_number' => ['nullable', 'string', 'max:80'],
            'legal_representative_document_type' => ['nullable', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'tax_document' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_tax_document' => ['sometimes', 'boolean'],
            'report_footer' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'document_number' => ['nullable', 'string', 'max:80'],
            'work_type' => ['nullable', 'string', 'max:80'],
            'is_certified_guide' => ['sometimes', 'boolean'],
            'id_front' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'id_back' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }
}
