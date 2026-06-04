<?php

namespace App\Http\Requests\User;

use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('users.assign-roles') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        return [
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,name'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->user()?->hasRole('super_admin')) {
                return;
            }

            if (array_intersect($this->input('roles', []), ['super_admin', 'admin']) !== []) {
                $validator->errors()->add('roles', 'Solo un super administrador puede asignar roles administrativos.');
            }
        });
    }
}
