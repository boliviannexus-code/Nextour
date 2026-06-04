<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CreditPurchaseRequest;
use App\Models\RegistrationRequest;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
        $revenueStatuses = [
            TourBooking::STATUS_CONFIRMED,
            TourBooking::STATUS_COMPLETED,
        ];
        $validBookingStatuses = [
            TourBooking::STATUS_PENDING,
            TourBooking::STATUS_CONFIRMED,
            TourBooking::STATUS_COMPLETED,
        ];
        $monthStart = now()->startOfMonth();

        return view('dashboard.index', [
            'dashboardCompany' => $company,
            'totalCompanies' => Company::query()->count(),
            'activeCompanies' => Company::query()->where('is_active', true)->count(),
            'approvedCompanies' => Company::query()->where('approval_status', RegistrationRequest::STATUS_APPROVED)->count(),
            'pendingRegistrationRequests' => RegistrationRequest::query()
                ->whereIn('status', [
                    RegistrationRequest::STATUS_PENDING,
                    RegistrationRequest::STATUS_IN_REVIEW,
                    RegistrationRequest::STATUS_OBSERVED,
                ])
                ->count(),
            'totalTours' => Tour::query()->count(),
            'activeTours' => Tour::query()->where('status', Tour::STATUS_ACTIVE)->count(),
            'bookableTours' => Tour::query()->publiclyBookable()->count(),
            'pendingReviewTours' => Tour::query()->where('review_status', Tour::REVIEW_PENDING)->count(),
            'totalBookings' => TourBooking::query()->count(),
            'confirmedBookings' => TourBooking::query()->where('status', TourBooking::STATUS_CONFIRMED)->count(),
            'totalTourists' => TourBooking::query()->whereIn('status', $validBookingStatuses)->sum('people'),
            'monthlyTourists' => TourBooking::query()
                ->whereIn('status', $validBookingStatuses)
                ->where('created_at', '>=', $monthStart)
                ->sum('people'),
            'totalRevenue' => TourBooking::query()->whereIn('status', $revenueStatuses)->sum('total_usd'),
            'monthlyRevenue' => TourBooking::query()
                ->whereIn('status', $revenueStatuses)
                ->where('created_at', '>=', $monthStart)
                ->sum('total_usd'),
            'heldBookingsCount' => TourBooking::query()
                ->where('credit_status', TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS)
                ->count(),
            'pendingCreditPurchaseRequests' => CreditPurchaseRequest::query()
                ->where('status', CreditPurchaseRequest::STATUS_PENDING)
                ->count(),
            'topTours' => Tour::query()
                ->with('company')
                ->withCount([
                    'bookings as bookings_count' => fn ($query) => $query->whereIn('status', $validBookingStatuses),
                ])
                ->withSum([
                    'bookings as revenue_usd' => fn ($query) => $query->whereIn('status', $revenueStatuses),
                ], 'total_usd')
                ->orderByDesc('revenue_usd')
                ->orderByDesc('bookings_count')
                ->limit(6)
                ->get(),
            'latestBookings' => TourBooking::query()
                ->with(['tour.company', 'user'])
                ->latest()
                ->limit(8)
                ->get(),
            'companiesNeedingAttention' => Company::query()
                ->whereIn('approval_status', [
                    RegistrationRequest::STATUS_PENDING,
                    RegistrationRequest::STATUS_IN_REVIEW,
                    RegistrationRequest::STATUS_OBSERVED,
                ])
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
