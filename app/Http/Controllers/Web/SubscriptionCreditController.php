<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyCreditMovement;
use App\Models\MonetizationAuditLog;
use App\Models\SubscriptionSetting;
use App\Models\TourBooking;
use App\Services\BookingLimitService;
use App\Services\SubscriptionService;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class SubscriptionCreditController extends Controller
{
    public function __construct(
        private readonly BookingLimitService $bookingLimits,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function index(Request $request): View
    {
        $setting = $this->subscriptions->getActiveConfiguration()
            ?? SubscriptionSetting::query()->create($this->defaultSettings());

        if (! CompanyContext::isGlobalAdmin($request->user())) {
            $company = CompanyContext::activeCompany($request->user());
            abort_unless($company, 403);

            $bookingQuery = TourBooking::query()
                ->whereHas('tour', fn (Builder $query): Builder => $query->where('company_id', $company->id));

            return view('subscriptions.company', [
                'setting' => $setting,
                'company' => $company,
                'status' => $this->bookingLimits->getCompanyStatus($company),
                'movements' => $company->creditMovements()
                    ->with(['booking', 'tour', 'creator'])
                    ->latest()
                    ->limit(20)
                    ->get(),
                'heldBookings' => (clone $bookingQuery)
                    ->with('tour')
                    ->where('credit_status', TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS)
                    ->where('visible_to_company', false)
                    ->oldest()
                    ->limit(20)
                    ->get(),
                'releasedLogs' => MonetizationAuditLog::query()
                    ->with(['booking', 'tour'])
                    ->where('company_id', $company->id)
                    ->where('event_type', MonetizationAuditLog::EVENT_BOOKING_RELEASED)
                    ->latest()
                    ->limit(20)
                    ->get(),
            ]);
        }

        $companies = Company::query()
            ->with('creditBalance')
            ->orderBy('name')
            ->get()
            ->map(function (Company $company): array {
                $status = $this->bookingLimits->getCompanyStatus($company);

                return [
                    'company' => $company,
                    'status' => $status,
                    'held_count' => TourBooking::query()
                        ->where('credit_status', TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS)
                        ->whereHas('tour', fn (Builder $query): Builder => $query->where('company_id', $company->id))
                        ->count(),
                ];
            });

        return view('subscriptions.index', [
            'setting' => $setting,
            'companies' => $companies,
            'movements' => CompanyCreditMovement::query()
                ->with(['company', 'booking', 'tour', 'creator'])
                ->latest()
                ->limit(25)
                ->get(),
            'heldBookings' => TourBooking::query()
                ->with(['tour.company'])
                ->where('credit_status', TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS)
                ->where('visible_to_company', false)
                ->oldest()
                ->limit(25)
                ->get(),
            'auditLogs' => MonetizationAuditLog::query()
                ->with(['company', 'booking', 'tour', 'creator'])
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate([
            'free_active_tours_limit' => ['required', 'integer', 'min:0', 'max:999'],
            'free_daily_pax_limit' => ['required', 'integer', 'min:0', 'max:99999'],
            'free_weekly_pax_limit' => ['required', 'integer', 'min:0', 'max:99999'],
            'credits_per_booking' => ['required', 'integer', 'min:1', 'max:999'],
            'credit_price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'warning_thresholds' => ['required', 'array', 'min:1'],
            'warning_thresholds.*' => ['integer', 'min:1', 'max:100'],
        ]);

        $data['warning_thresholds'] = collect($data['warning_thresholds'])
            ->map(fn (mixed $threshold): int => (int) $threshold)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $data['daily_warning_threshold'] = Arr::last($data['warning_thresholds']);
        $data['weekly_warning_threshold'] = Arr::last($data['warning_thresholds']);
        $data['is_active'] = true;

        $setting = $this->subscriptions->getActiveConfiguration();
        $before = $setting?->only(array_keys($data));

        if ($setting) {
            $setting->update($data);
        } else {
            $setting = SubscriptionSetting::query()->create($data);
        }

        $this->subscriptions->recordAudit(
            eventType: MonetizationAuditLog::EVENT_CONFIGURATION_CHANGED,
            description: 'Configuracion global de suscripciones actualizada.',
            createdBy: $request->user(),
            metadata: [
                'before' => $before,
                'after' => $setting->fresh()?->only(array_keys($data)),
            ],
        );

        return back()->with('status', 'Configuracion de suscripciones actualizada.');
    }

    public function addCredits(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'credits' => ['required', 'integer', 'min:1', 'max:1000000'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $company = Company::query()->findOrFail($data['company_id']);
        $reason = $data['reason'] ?: 'Carga manual de creditos por super admin.';
        $beforeHeld = TourBooking::query()
            ->where('credit_status', TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS)
            ->whereHas('tour', fn (Builder $query): Builder => $query->where('company_id', $company->id))
            ->count();

        $this->subscriptions->addCredits(
            company: $company,
            credits: (int) $data['credits'],
            reason: $reason,
            createdBy: $request->user(),
            movementType: CompanyCreditMovement::TYPE_MANUAL_ADDITION,
        );

        $afterHeld = TourBooking::query()
            ->where('credit_status', TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS)
            ->whereHas('tour', fn (Builder $query): Builder => $query->where('company_id', $company->id))
            ->count();
        $released = max(0, $beforeHeld - $afterHeld);

        return back()->with('status', "Creditos agregados. Reservas liberadas: {$released}.");
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
    }

    private function defaultSettings(): array
    {
        return [
            'free_active_tours_limit' => 2,
            'free_daily_pax_limit' => 5,
            'free_weekly_pax_limit' => 20,
            'daily_warning_threshold' => 100,
            'weekly_warning_threshold' => 100,
            'warning_thresholds' => [50, 75, 90, 100],
            'credits_per_booking' => 1,
            'credit_price' => 0,
            'is_active' => true,
        ];
    }
}
