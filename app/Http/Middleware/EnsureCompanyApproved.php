<?php

namespace App\Http\Middleware;

use App\Models\RegistrationRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasRole('super_admin')) {
            return $next($request);
        }

        $company = $user->company;

        if (! $company || $company->approval_status === RegistrationRequest::STATUS_APPROVED) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Su empresa aun no ha sido aprobada por la administracion.');
        }

        return redirect()
            ->route('dashboard')
            ->with('warning', 'Su empresa aun no ha sido aprobada por la administracion.');
    }
}
