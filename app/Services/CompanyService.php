<?php

namespace App\Services;

use App\Models\Company;
use App\Models\RegistrationRequest;
use App\Support\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyService
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return CompanyContext::scope(Company::query(), column: 'id')
            ->with('registrationRequest')
            ->withCount('users')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Company
    {
        $data = $this->normalize($data, true);

        if (($data['logo'] ?? null) instanceof UploadedFile) {
            $data['logo_path'] = $data['logo']->store('companies/logos', 'public');
        }

        if (($data['tax_document'] ?? null) instanceof UploadedFile) {
            $data['tax_document_path'] = $data['tax_document']->store('companies/tax-documents', 'public');
        }

        unset($data['logo'], $data['tax_document'], $data['remove_logo'], $data['remove_tax_document']);

        return Company::query()->create($data);
    }

    public function update(Company $company, array $data): Company
    {
        return DB::transaction(function () use ($company, $data): Company {
            $company->loadMissing('registrationRequest.independentProfile');
            $isIndependent = $company->registrationRequest?->type === RegistrationRequest::TYPE_INDEPENDENT;

            if ($isIndependent) {
                $this->updateIndependentProfile($company, $data);

                if (isset($data['first_name'], $data['last_name'])) {
                    $data['legal_name'] = trim($data['first_name'].' '.$data['last_name']);
                }

                $data = array_intersect_key($data, array_flip([
                    'name',
                    'legal_name',
                    'phone',
                    'email',
                    'description',
                    'address',
                    'report_footer',
                    'is_active',
                ]));
            }

            $data = $this->normalize($data);

            if (! empty($data['remove_logo']) && $company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
                $data['logo_path'] = null;
            }

            if (! empty($data['remove_tax_document']) && $company->tax_document_path) {
                Storage::disk('public')->delete($company->tax_document_path);
                $data['tax_document_path'] = null;
            }

            if (($data['logo'] ?? null) instanceof UploadedFile) {
                if ($company->logo_path) {
                    Storage::disk('public')->delete($company->logo_path);
                }

                $data['logo_path'] = $data['logo']->store('companies/logos', 'public');
            }

            if (($data['tax_document'] ?? null) instanceof UploadedFile) {
                if ($company->tax_document_path) {
                    Storage::disk('public')->delete($company->tax_document_path);
                }

                $data['tax_document_path'] = $data['tax_document']->store('companies/tax-documents', 'public');
            }

            unset(
                $data['logo'],
                $data['tax_document'],
                $data['remove_logo'],
                $data['remove_tax_document'],
                $data['first_name'],
                $data['last_name'],
                $data['document_number'],
                $data['work_type'],
                $data['is_certified_guide'],
                $data['id_front'],
                $data['id_back'],
                $data['profile_photo'],
            );

            $company->update($data);

            return $company->refresh();
        });
    }

    public function delete(Company $company): bool
    {
        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        if ($company->tax_document_path) {
            Storage::disk('public')->delete($company->tax_document_path);
        }

        return (bool) $company->delete();
    }

    private function normalize(array $data, ?bool $defaultActive = null): array
    {
        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'];
        } elseif ($defaultActive !== null) {
            $data['is_active'] = $defaultActive;
        }

        return $data;
    }

    private function updateIndependentProfile(Company $company, array $data): void
    {
        $profile = $company->registrationRequest?->independentProfile;

        if (! $profile) {
            return;
        }

        $profileData = [];

        foreach ([
            'first_name',
            'last_name',
            'document_number',
            'phone',
            'email',
            'address',
            'work_type',
            'description',
            'is_certified_guide',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $profileData[$field] = $field === 'is_certified_guide'
                    ? (bool) $data[$field]
                    : $data[$field];
            }
        }

        foreach ([
            'id_front' => ['id_front_path', 'registration/independents/id-front'],
            'id_back' => ['id_back_path', 'registration/independents/id-back'],
            'profile_photo' => ['profile_photo_path', 'registration/independents/profile-photos'],
        ] as $input => [$column, $directory]) {
            if (($data[$input] ?? null) instanceof UploadedFile) {
                if ($profile->{$column}) {
                    Storage::disk('public')->delete($profile->{$column});
                }

                $profileData[$column] = $data[$input]->store($directory, 'public');
            }
        }

        if ($profileData !== []) {
            $profile->update($profileData);
        }
    }
}
