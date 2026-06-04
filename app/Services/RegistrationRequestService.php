<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\IndependentProfile;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Notifications\RegistrationRequestApproved;
use App\Notifications\RegistrationRequestReceived;
use App\Notifications\RegistrationRequestRejected;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Image\Image;

class RegistrationRequestService
{
    private const IMAGE_MAX_WIDTH = 1600;

    private const IMAGE_WEBP_QUALITY = 78;

    public function createCompanyRequest(array $data): RegistrationRequest
    {
        return DB::transaction(function () use ($data): RegistrationRequest {
            $company = Company::query()->create([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'],
                'tax_id' => $data['tax_id'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'website' => $data['website'] ?? null,
                'description' => $data['description'],
                'address' => $data['address'],
                'city' => $data['city'],
                'country' => $data['country'],
                'logo_path' => $this->storeOptimizedImage($data['logo'], 'registration/companies/logos'),
                'tax_document_path' => $this->storeOptimizedImage($data['tax_document'], 'registration/companies/tax-documents'),
                'legal_representative_first_name' => $data['legal_representative_first_name'],
                'legal_representative_last_name' => $data['legal_representative_last_name'],
                'legal_representative_document_number' => $data['legal_representative_document_number'],
                'legal_representative_document_type' => $data['legal_representative_document_type'],
                'is_active' => false,
                'approval_status' => RegistrationRequest::STATUS_PENDING,
            ]);

            $user = User::query()->create([
                'company_id' => $company->id,
                'name' => trim($data['legal_representative_first_name'].' '.$data['legal_representative_last_name']),
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => true,
            ]);
            $user->assignRole('empresa_pendiente');

            $request = RegistrationRequest::query()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'type' => RegistrationRequest::TYPE_COMPANY,
                'status' => RegistrationRequest::STATUS_PENDING,
            ]);

            $this->recordReview($request, null, RegistrationRequest::STATUS_PENDING, 'Empresa registrada.');
            $user->notify(new RegistrationRequestReceived($request));

            return $request->load(['user', 'company']);
        });
    }

    public function createIndependentRequest(array $data): RegistrationRequest
    {
        return DB::transaction(function () use ($data): RegistrationRequest {
            $company = Company::query()->create([
                'name' => $data['commercial_name'],
                'legal_name' => trim($data['first_name'].' '.$data['last_name']),
                'phone' => $data['phone'],
                'email' => $data['email'],
                'description' => $data['description'],
                'address' => $data['address'],
                'is_active' => false,
                'approval_status' => RegistrationRequest::STATUS_PENDING,
            ]);

            $user = User::query()->create([
                'company_id' => $company->id,
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => true,
            ]);
            $user->assignRole('empresa_pendiente');

            $profile = IndependentProfile::query()->create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'document_number' => $data['document_number'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'work_type' => $data['work_type'],
                'description' => $data['description'],
                'is_certified_guide' => (bool) $data['is_certified_guide'],
                'id_front_path' => $this->storeOptimizedImage($data['id_front'], 'registration/independents/id-front'),
                'id_back_path' => $this->storeOptimizedImage($data['id_back'], 'registration/independents/id-back'),
                'profile_photo_path' => $this->storeOptimizedImage($data['profile_photo'], 'registration/independents/profile-photos'),
            ]);

            $request = RegistrationRequest::query()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'independent_profile_id' => $profile->id,
                'type' => RegistrationRequest::TYPE_INDEPENDENT,
                'status' => RegistrationRequest::STATUS_PENDING,
            ]);

            $this->recordReview($request, null, RegistrationRequest::STATUS_PENDING, 'Solicitud independiente registrada.');
            $user->notify(new RegistrationRequestReceived($request));

            return $request->load(['user', 'independentProfile']);
        });
    }

    public function approve(RegistrationRequest $request, User $approver): RegistrationRequest
    {
        abort_unless($approver->hasRole('super_admin'), 403);
        abort_unless(in_array($request->status, [RegistrationRequest::STATUS_PENDING, RegistrationRequest::STATUS_IN_REVIEW], true), 409);

        return DB::transaction(function () use ($request, $approver): RegistrationRequest {
            $statusBefore = $request->status;

            $request->update([
                'status' => RegistrationRequest::STATUS_APPROVED,
                'last_reviewed_at' => now(),
                'approved_at' => now(),
                'approved_by' => $approver->id,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]);

            $request->user->update(['is_active' => true]);
            $request->user->syncRoles(['gerente']);
            $request->company?->update([
                'is_active' => true,
                'approval_status' => RegistrationRequest::STATUS_APPROVED,
                'last_reviewed_at' => now(),
                'approved_at' => now(),
                'approved_by' => $approver->id,
            ]);
            $this->recordReview($request, $statusBefore, RegistrationRequest::STATUS_APPROVED, 'Solicitud aprobada. Cambio de rol realizado: empresa_pendiente -> gerente.', $approver);
            $request->user->notify(new RegistrationRequestApproved($request));

            return $request->refresh();
        });
    }

    public function observe(RegistrationRequest $request, User $reviewer, string $observation): RegistrationRequest
    {
        abort_unless($reviewer->hasRole('super_admin'), 403);
        abort_unless(in_array($request->status, [RegistrationRequest::STATUS_PENDING, RegistrationRequest::STATUS_IN_REVIEW], true), 409);

        return DB::transaction(function () use ($request, $reviewer, $observation): RegistrationRequest {
            $statusBefore = $request->status;

            $request->update([
                'status' => RegistrationRequest::STATUS_OBSERVED,
                'last_reviewed_at' => now(),
                'rejection_reason' => $observation,
            ]);

            $request->user->update(['is_active' => true]);
            $request->user->syncRoles(['empresa_pendiente']);
            $request->company?->update([
                'is_active' => false,
                'approval_status' => RegistrationRequest::STATUS_OBSERVED,
                'last_reviewed_at' => now(),
            ]);
            $this->recordReview($request, $statusBefore, RegistrationRequest::STATUS_OBSERVED, $observation, $reviewer);

            return $request->refresh();
        });
    }

    public function reject(RegistrationRequest $request, User $rejector, string $reason): RegistrationRequest
    {
        abort_unless($rejector->hasRole('super_admin'), 403);
        abort_unless(in_array($request->status, [RegistrationRequest::STATUS_PENDING, RegistrationRequest::STATUS_IN_REVIEW, RegistrationRequest::STATUS_OBSERVED], true), 409);

        DB::transaction(function () use ($request, $rejector, $reason): void {
            $statusBefore = $request->status;

            $request->update([
                'status' => RegistrationRequest::STATUS_REJECTED,
                'last_reviewed_at' => now(),
                'rejected_at' => now(),
                'rejected_by' => $rejector->id,
                'rejection_reason' => $reason,
            ]);

            $request->user->update(['is_active' => true]);
            $request->user->syncRoles(['empresa_pendiente']);
            $request->company?->update([
                'is_active' => false,
                'approval_status' => RegistrationRequest::STATUS_REJECTED,
                'last_reviewed_at' => now(),
            ]);
            $this->recordReview($request, $statusBefore, RegistrationRequest::STATUS_REJECTED, $reason, $rejector);
        });

        $request->user->notify(new RegistrationRequestRejected($request, $reason));

        return $request->refresh();
    }

    public function resubmit(RegistrationRequest $request, User $actor): RegistrationRequest
    {
        abort_unless(in_array($request->status, [RegistrationRequest::STATUS_PENDING, RegistrationRequest::STATUS_OBSERVED], true), 409);
        abort_unless((int) $request->user_id === (int) $actor->id, 403);

        return DB::transaction(function () use ($request): RegistrationRequest {
            $statusBefore = $request->status;

            $request->update([
                'status' => RegistrationRequest::STATUS_IN_REVIEW,
                'submitted_at' => now(),
                'approved_at' => null,
                'approved_by' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]);

            $request->user->update(['is_active' => true]);
            $request->user->syncRoles(['empresa_pendiente']);
            $request->company?->update([
                'is_active' => false,
                'approval_status' => RegistrationRequest::STATUS_IN_REVIEW,
            ]);
            $this->recordReview($request, $statusBefore, RegistrationRequest::STATUS_IN_REVIEW, 'Solicitud enviada para revision.', $request->user);
            $request->user->notify(new RegistrationRequestReceived($request));

            return $request->refresh();
        });
    }

    private function recordReview(
        RegistrationRequest $request,
        ?string $statusBefore,
        string $statusAfter,
        ?string $observation = null,
        ?User $reviewer = null,
    ): CompanyReview {
        return CompanyReview::query()->create([
            'company_id' => $request->company_id,
            'registration_request_id' => $request->id,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'observation' => $observation,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
            'ip_address' => request()?->ip(),
        ]);
    }

    private function storeOptimizedImage(UploadedFile $image, string $directory): string
    {
        if (! extension_loaded('gd') && ! class_exists(\Imagick::class)) {
            throw ValidationException::withMessages([
                'image' => 'No se pudo optimizar la imagen. Activa GD o Imagick en PHP para convertir imagenes a WebP.',
            ]);
        }

        $path = trim($directory, '/').'/'.Str::uuid()->toString().'.webp';
        $absolutePath = Storage::disk('public')->path($path);

        Storage::disk('public')->makeDirectory($directory);

        $optimized = Image::load((string) $image->getRealPath());

        if ($optimized->getWidth() > self::IMAGE_MAX_WIDTH) {
            $optimized->width(self::IMAGE_MAX_WIDTH);
        }

        $optimized->quality(self::IMAGE_WEBP_QUALITY)->save($absolutePath);

        return $path;
    }
}
