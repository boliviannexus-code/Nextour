<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicRegistration\StoreCompanyRegistrationRequest;
use App\Http\Requests\PublicRegistration\StoreIndependentRegistrationRequest;
use App\Services\RegistrationRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BusinessRegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationRequestService $registrationRequests,
    ) {}

    public function select(): View
    {
        return view('public.business-registration.select');
    }

    public function company(): View
    {
        return view('public.business-registration.company');
    }

    public function storeCompany(StoreCompanyRegistrationRequest $request): RedirectResponse
    {
        $registrationRequest = $this->registrationRequests->createCompanyRequest($request->validated());

        Auth::login($registrationRequest->user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Registro recibido. Envia tu solicitud para que un Super Administrador revise la informacion.');
    }

    public function independent(): View
    {
        return view('public.business-registration.independent');
    }

    public function storeIndependent(StoreIndependentRegistrationRequest $request): RedirectResponse
    {
        $registrationRequest = $this->registrationRequests->createIndependentRequest($request->validated());

        Auth::login($registrationRequest->user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Registro recibido. Envia tu solicitud para que un Super Administrador revise la informacion.');
    }

    public function thanks(): View
    {
        return view('public.business-registration.thanks');
    }
}
