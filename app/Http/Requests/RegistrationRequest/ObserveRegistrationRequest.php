<?php

namespace App\Http\Requests\RegistrationRequest;

use Illuminate\Foundation\Http\FormRequest;

class ObserveRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'observation' => ['required', 'string', 'max:2000'],
        ];
    }
}
