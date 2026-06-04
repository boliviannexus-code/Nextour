<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (auth()->user()?->hasAnyRole(['empresa_pendiente', 'registration_applicant'])) {
            $company = auth()->user()?->company?->load([
                'registrationRequest.reviews.reviewer',
                'reviews.reviewer',
            ]);

            return view('dashboard.pending-company', [
                'company' => $company,
                'registrationRequest' => $company?->registrationRequest,
            ]);
        }

        if (auth()->user()?->hasAnyRole(['manager', 'gerente'])) {
            return redirect()->route('manager.dashboard');
        }

        Gate::authorize('dashboard.view');

        $company = CompanyContext::activeCompany();

        return view('dashboard.index', [
            'dashboardCompany' => $company,
            'totalUsers' => User::query()->count(),
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'totalRoles' => Role::query()->count(),
            'totalPermissions' => Permission::query()->count(),
            'totalCompanies' => Company::query()->count(),
            'recentAudits' => Audit::query()
                ->with('user')
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
