<?php

namespace Tests\Feature\Registration;

use App\Models\Company;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Http\Requests\PublicRegistration\StoreIndependentRegistrationRequest;
use App\Notifications\RegistrationRequestReceived;
use App\Services\RegistrationRequestService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pending_company_user_sees_limited_dashboard_and_cannot_open_operational_modules(): void
    {
        $company = Company::factory()->create([
            'is_active' => false,
            'approval_status' => RegistrationRequest::STATUS_PENDING,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $user->assignRole('empresa_pendiente');

        RegistrationRequest::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'type' => RegistrationRequest::TYPE_COMPANY,
            'status' => RegistrationRequest::STATUS_PENDING,
        ]);

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Su empresa aun no ha sido aprobada');

        $user->givePermissionTo('tours.view');

        $this
            ->actingAs($user)
            ->get(route('tours.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('warning', 'Su empresa aun no ha sido aprobada por la administracion.');
    }

    public function test_super_admin_can_observe_resubmitted_request_and_approve_with_gerente_role(): void
    {
        $service = app(RegistrationRequestService::class);

        $company = Company::factory()->create([
            'is_active' => false,
            'approval_status' => RegistrationRequest::STATUS_PENDING,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $user->assignRole('empresa_pendiente');

        $superAdmin = User::factory()->create(['company_id' => null, 'is_active' => true]);
        $superAdmin->assignRole(Role::findByName('super_admin'));

        $request = RegistrationRequest::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'type' => RegistrationRequest::TYPE_COMPANY,
            'status' => RegistrationRequest::STATUS_PENDING,
        ]);

        $observed = $service->observe($request->load(['user', 'company']), $superAdmin, 'NIT invalido.');

        $this->assertSame(RegistrationRequest::STATUS_OBSERVED, $observed->status);
        $this->assertTrue($observed->user->hasRole('empresa_pendiente'));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_active' => false,
            'approval_status' => RegistrationRequest::STATUS_OBSERVED,
        ]);
        $this->assertDatabaseHas('company_reviews', [
            'company_id' => $company->id,
            'status_before' => RegistrationRequest::STATUS_PENDING,
            'status_after' => RegistrationRequest::STATUS_OBSERVED,
            'observation' => 'NIT invalido.',
            'reviewed_by' => $superAdmin->id,
        ]);

        $resubmitted = $service->resubmit($observed->refresh()->load(['user', 'company']), $user);

        $this->assertSame(RegistrationRequest::STATUS_IN_REVIEW, $resubmitted->status);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'approval_status' => RegistrationRequest::STATUS_IN_REVIEW,
        ]);

        $approved = $service->approve($resubmitted->refresh()->load(['user', 'company']), $superAdmin);

        $this->assertSame(RegistrationRequest::STATUS_APPROVED, $approved->status);
        $this->assertTrue($approved->user->hasRole('gerente'));
        $this->assertFalse($approved->user->hasRole('empresa_pendiente'));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_active' => true,
            'approval_status' => RegistrationRequest::STATUS_APPROVED,
            'approved_by' => $superAdmin->id,
        ]);
        $this->assertDatabaseHas('company_reviews', [
            'company_id' => $company->id,
            'status_before' => RegistrationRequest::STATUS_IN_REVIEW,
            'status_after' => RegistrationRequest::STATUS_APPROVED,
            'reviewed_by' => $superAdmin->id,
        ]);
    }

    public function test_independent_registration_requires_commercial_name(): void
    {
        $request = new StoreIndependentRegistrationRequest;

        $validator = Validator::make([
            'first_name' => 'Ana',
            'last_name' => 'Quispe',
            'document_number' => 'CI-123',
            'phone' => '70000000',
            'email' => 'ana@example.test',
            'address' => 'Calle 1',
            'description' => 'Guia principiante.',
            'work_type' => 'guide',
            'is_certified_guide' => '0',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('commercial_name', $validator->errors()->toArray());
    }

    public function test_independent_registration_creates_internal_company_using_commercial_name(): void
    {
        Storage::fake('public');
        Notification::fake();

        $service = app(RegistrationRequestService::class);

        $registrationRequest = $service->createIndependentRequest([
            'commercial_name' => 'Rutas Andinas Ana',
            'first_name' => 'Ana',
            'last_name' => 'Quispe',
            'document_number' => 'CI-123',
            'phone' => '70000000',
            'email' => 'ana@example.test',
            'address' => 'Calle 1',
            'description' => 'Guia principiante.',
            'work_type' => 'guide',
            'is_certified_guide' => false,
            'password' => 'password',
            'id_front' => $this->pngUpload('id-front.png'),
            'id_back' => $this->pngUpload('id-back.png'),
            'profile_photo' => $this->pngUpload('profile-photo.png'),
        ]);

        $this->assertSame(RegistrationRequest::TYPE_INDEPENDENT, $registrationRequest->type);
        $this->assertSame(RegistrationRequest::STATUS_PENDING, $registrationRequest->status);
        $this->assertSame('Rutas Andinas Ana', $registrationRequest->company->name);
        $this->assertSame('Ana Quispe', $registrationRequest->company->legal_name);
        $this->assertSame($registrationRequest->company_id, $registrationRequest->user->company_id);
        $this->assertTrue($registrationRequest->user->hasRole('empresa_pendiente'));

        $this->assertDatabaseHas('independent_profiles', [
            'id' => $registrationRequest->independent_profile_id,
            'first_name' => 'Ana',
            'last_name' => 'Quispe',
            'document_number' => 'CI-123',
        ]);
        $this->assertDatabaseHas('company_reviews', [
            'company_id' => $registrationRequest->company_id,
            'registration_request_id' => $registrationRequest->id,
            'status_after' => RegistrationRequest::STATUS_PENDING,
            'observation' => 'Solicitud independiente registrada.',
        ]);
        Storage::disk('public')->assertExists($registrationRequest->independentProfile->id_front_path);
        Storage::disk('public')->assertExists($registrationRequest->independentProfile->id_back_path);
        Storage::disk('public')->assertExists($registrationRequest->independentProfile->profile_photo_path);
        Notification::assertSentTo($registrationRequest->user, RegistrationRequestReceived::class);
    }

    public function test_company_registration_persists_request_and_images_without_image_optimizer(): void
    {
        Storage::fake('public');
        Notification::fake();

        $this
            ->post(route('business-register.company.store'), [
                'name' => 'Andes Travel',
                'legal_name' => 'Andes Travel SRL',
                'tax_id' => '123456789',
                'phone' => '70000000',
                'email' => 'empresa@example.test',
                'address' => 'Av. Siempre Viva 123',
                'city' => 'La Paz',
                'country' => 'Bolivia',
                'website' => 'https://example.test',
                'description' => 'Operadora turistica local.',
                'legal_representative_first_name' => 'Luis',
                'legal_representative_last_name' => 'Mamani',
                'legal_representative_document_number' => 'CI-456',
                'legal_representative_document_type' => 'CI',
                'password' => 'password',
                'password_confirmation' => 'password',
                'logo' => $this->pngUpload('logo.png'),
                'tax_document' => $this->pngUpload('nit.png'),
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $company = Company::query()->where('email', 'empresa@example.test')->firstOrFail();

        $this->assertAuthenticated();
        $this->assertSame(RegistrationRequest::STATUS_PENDING, $company->approval_status);
        $this->assertDatabaseHas('registration_requests', [
            'company_id' => $company->id,
            'type' => RegistrationRequest::TYPE_COMPANY,
            'status' => RegistrationRequest::STATUS_PENDING,
        ]);
        $this->assertNotNull($company->logo_path);
        $this->assertNotNull($company->tax_document_path);
        Storage::disk('public')->assertExists($company->logo_path);
        Storage::disk('public')->assertExists($company->tax_document_path);
    }

    private function pngUpload(string $name): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            true
        );

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
