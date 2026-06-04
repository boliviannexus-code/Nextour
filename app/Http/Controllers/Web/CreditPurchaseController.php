<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\CreditPurchaseRequest;
use App\Models\MonetizationAuditLog;
use App\Services\SubscriptionService;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CreditPurchaseController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()?->can('credits.purchase'), 403);

        if (CompanyContext::isGlobalAdmin($request->user())) {
            return redirect()->route('credit-purchases.requests.index');
        }

        $company = CompanyContext::activeCompany($request->user());
        if (! $company) {
            return redirect()
                ->route('dashboard')
                ->with('warning', 'Necesitas una empresa activa para comprar creditos.');
        }

        return view('credits.purchases.index', [
            'company' => $company,
            'balance' => $this->subscriptions->getCompanyBalance($company),
            'packages' => CreditPackage::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'purchaseRequests' => $company->creditPurchaseRequests()->with('package')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('credits.purchase'), 403);
        abort_if(CompanyContext::isGlobalAdmin($request->user()), 403);

        $company = CompanyContext::activeCompany($request->user());
        if (! $company) {
            return redirect()
                ->route('dashboard')
                ->with('warning', 'Necesitas una empresa activa para comprar creditos.');
        }

        $data = $request->validate([
            'credit_package_id' => ['required', 'exists:credit_packages,id'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $package = CreditPackage::query()->active()->findOrFail($data['credit_package_id']);
        $path = $request->file('payment_proof')->store('credits/purchase-proofs', 'public');

        $purchaseRequest = CreditPurchaseRequest::query()->create([
            'company_id' => $company->id,
            'credit_package_id' => $package->id,
            'requested_credits' => $package->credits_amount,
            'amount' => $package->price,
            'currency' => CreditPackage::CURRENCY_USD,
            'payment_proof_path' => $path,
            'status' => CreditPurchaseRequest::STATUS_PENDING,
        ]);

        $this->subscriptions->recordAudit(
            eventType: MonetizationAuditLog::EVENT_PURCHASE_REQUESTED,
            description: 'Solicitud de compra de creditos enviada.',
            company: $company,
            createdBy: $request->user(),
            metadata: ['purchase_request_id' => $purchaseRequest->id, 'currency' => CreditPackage::CURRENCY_USD],
        );

        return back()->with('success', 'Solicitud enviada para revision.');
    }

    public function adminIndex(Request $request): View
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $query = CreditPurchaseRequest::query()->with(['company', 'package', 'approver', 'rejector'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        return view('credits.requests.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'statuses' => CreditPurchaseRequest::STATUSES,
            'companies' => \App\Models\Company::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['status', 'company_id', 'date_from', 'date_to']),
        ]);
    }

    public function approve(Request $request, CreditPurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        DB::transaction(function () use ($purchaseRequest, $request): void {
            $purchaseRequest = CreditPurchaseRequest::query()->whereKey($purchaseRequest->id)->lockForUpdate()->firstOrFail();
            abort_if($purchaseRequest->status !== CreditPurchaseRequest::STATUS_PENDING, 422, 'La solicitud ya fue procesada.');

            $purchaseRequest->update([
                'status' => CreditPurchaseRequest::STATUS_APPROVED,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $this->subscriptions->addCredits(
                company: $purchaseRequest->company,
                credits: $purchaseRequest->requested_credits,
                reason: 'Compra de paquete de creditos aprobada por Super Admin.',
                createdBy: $request->user(),
                movementType: \App\Models\CompanyCreditMovement::TYPE_PURCHASE,
            );

            $this->subscriptions->recordAudit(
                eventType: MonetizationAuditLog::EVENT_PURCHASE_APPROVED,
                description: 'Solicitud de compra de creditos aprobada.',
                company: $purchaseRequest->company,
                createdBy: $request->user(),
                metadata: ['purchase_request_id' => $purchaseRequest->id, 'amount' => $purchaseRequest->amount, 'currency' => $purchaseRequest->currency],
            );
        });

        return back()->with('success', 'Compra aprobada y creditos cargados.');
    }

    public function reject(Request $request, CreditPurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $data = $request->validate([
            'admin_observation' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($purchaseRequest, $request, $data): void {
            $purchaseRequest = CreditPurchaseRequest::query()->whereKey($purchaseRequest->id)->lockForUpdate()->firstOrFail();
            abort_if($purchaseRequest->status !== CreditPurchaseRequest::STATUS_PENDING, 422, 'La solicitud ya fue procesada.');

            $purchaseRequest->update([
                'status' => CreditPurchaseRequest::STATUS_REJECTED,
                'admin_observation' => $data['admin_observation'],
                'rejected_by' => $request->user()->id,
                'rejected_at' => now(),
            ]);

            $this->subscriptions->recordAudit(
                eventType: MonetizationAuditLog::EVENT_PURCHASE_REJECTED,
                description: 'Solicitud de compra de creditos rechazada.',
                company: $purchaseRequest->company,
                createdBy: $request->user(),
                metadata: ['purchase_request_id' => $purchaseRequest->id, 'observation' => $data['admin_observation']],
            );
        });

        return back()->with('success', 'Compra rechazada.');
    }
}
