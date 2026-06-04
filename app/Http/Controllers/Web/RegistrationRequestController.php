<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest\ObserveRegistrationRequest;
use App\Http\Requests\RegistrationRequest\RejectRegistrationRequest;
use App\Models\RegistrationRequest;
use App\Services\RegistrationRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationRequestController extends Controller
{
    public function __construct(
        private readonly RegistrationRequestService $registrationRequests,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $status = $request->query('status');

        return view('registration-requests.index', [
            'status' => is_string($status) ? $status : null,
            'requests' => RegistrationRequest::query()
                ->with(['user', 'company', 'independentProfile', 'approver', 'rejector'])
                ->status(is_string($status) && $status !== 'all' ? $status : null)
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function show(Request $request, RegistrationRequest $registrationRequest): View
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        return view('registration-requests.show', [
            'registrationRequest' => $registrationRequest->load(['user', 'company', 'independentProfile', 'approver', 'rejector', 'reviews.reviewer']),
        ]);
    }

    public function approve(Request $request, RegistrationRequest $registrationRequest): RedirectResponse
    {
        $this->registrationRequests->approve($registrationRequest->load(['user', 'company']), $request->user());

        return redirect()->route('registration-requests.show', $registrationRequest)->with('success', 'Solicitud aprobada correctamente.');
    }

    public function observe(ObserveRegistrationRequest $request, RegistrationRequest $registrationRequest): RedirectResponse
    {
        $this->registrationRequests->observe($registrationRequest->load(['user', 'company']), $request->user(), $request->validated('observation'));

        return redirect()->route('registration-requests.show', $registrationRequest)->with('success', 'Solicitud observada correctamente.');
    }

    public function reject(RejectRegistrationRequest $request, RegistrationRequest $registrationRequest): RedirectResponse
    {
        $this->registrationRequests->reject($registrationRequest->load('user'), $request->user(), $request->validated('rejection_reason'));

        return redirect()->route('registration-requests.show', $registrationRequest)->with('success', 'Solicitud rechazada correctamente.');
    }

    public function resubmit(Request $request, RegistrationRequest $registrationRequest): RedirectResponse
    {
        abort_unless($request->user()?->can('registration_requests.resubmit'), 403);

        $this->registrationRequests->resubmit($registrationRequest->load(['user', 'company']), $request->user());

        return redirect()->route('dashboard')->with('success', 'Solicitud enviada correctamente. Quedara en revision hasta una nueva respuesta.');
    }
}
