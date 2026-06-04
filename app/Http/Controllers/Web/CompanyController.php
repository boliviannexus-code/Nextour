<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\RegistrationRequest;
use App\Services\CompanyService;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $companies
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('companies.view'), 403);

        return view('companies.index', [
            'companies' => $this->companies->paginate(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('companies.create'), 403);

        if ($request->ajax()) {
            return view('companies.partials.create-form');
        }

        return view('companies.create');
    }

    public function store(StoreCompanyRequest $request): JsonResponse|RedirectResponse
    {
        $company = $this->companies->create($request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Empresa creada correctamente.',
                'data' => ['id' => $company->id],
            ], 201);
        }

        return redirect()->route('companies.index')->with('success', 'Empresa creada correctamente.');
    }

    public function show(Request $request, Company $company): View
    {
        abort_unless($request->user()?->can('companies.view') && CompanyContext::belongsToUser($company->id, $request->user()), 403);

        $company->load('registrationRequest')->loadCount('users');

        if ($request->ajax()) {
            return view('companies.partials.show', compact('company'));
        }

        return view('companies.show', compact('company'));
    }

    public function edit(Request $request, Company $company): View
    {
        abort_unless($request->user()?->can('companies.update') && CompanyContext::belongsToUser($company->id, $request->user()), 403);

        $company->load('registrationRequest.independentProfile');
        abort_if($company->registrationRequest?->status === RegistrationRequest::STATUS_REJECTED, 403);

        if ($request->ajax()) {
            return view('companies.partials.edit-form', compact('company'));
        }

        return view('companies.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse|RedirectResponse
    {
        abort_unless(CompanyContext::belongsToUser($company->id, $request->user()), 403);

        $company->loadMissing('registrationRequest');
        abort_if($company->registrationRequest?->status === RegistrationRequest::STATUS_REJECTED, 403);

        $data = $request->validated();

        if (! $request->user()?->hasRole('super_admin')) {
            unset($data['is_active']);
        }

        $company = $this->companies->update($company, $data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Empresa actualizada correctamente.',
                'data' => ['id' => $company->id],
            ]);
        }

        return redirect()->route('companies.index')->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        abort_unless(auth()->user()?->can('companies.delete') && CompanyContext::belongsToUser($company->id, auth()->user()), 403);

        $this->companies->delete($company);

        return redirect()->route('companies.index')->with('success', 'Empresa eliminada correctamente.');
    }
}
