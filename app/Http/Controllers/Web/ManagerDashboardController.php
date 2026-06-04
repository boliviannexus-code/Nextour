<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MonetizationAuditLog;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Services\BookingLimitService;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerDashboardController extends Controller
{
    public function __construct(
        private readonly BookingLimitService $bookingLimits,
    ) {}

    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->hasAnyRole(['manager', 'gerente']), 403);

        $company = CompanyContext::activeCompany($request->user());
        $tourQuery = CompanyContext::scope(Tour::query(), $request->user());
        $bookingQuery = TourBooking::query()
            ->whereHas('tour', fn (Builder $query): Builder => CompanyContext::scope($query, $request->user()));
        $visibleBookingQuery = (clone $bookingQuery)->where('visible_to_company', true);

        return view('dashboard.manager', [
            'dashboardCompany' => $company,
            'totalTours' => (clone $tourQuery)->count(),
            'activeTours' => (clone $tourQuery)->where('status', Tour::STATUS_ACTIVE)->count(),
            'pendingReviewTours' => (clone $tourQuery)->where('review_status', Tour::REVIEW_PENDING)->count(),
            'totalBookings' => (clone $visibleBookingQuery)->count(),
            'confirmedBookings' => (clone $visibleBookingQuery)->where('status', TourBooking::STATUS_CONFIRMED)->count(),
            'monthlyRevenue' => (clone $visibleBookingQuery)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_usd'),
            'categoriesCount' => Category::query()->where('is_active', true)->count(),
            'subscriptionStatus' => $company ? $this->bookingLimits->getCompanyStatus($company) : null,
            'heldBookings' => (clone $bookingQuery)
                ->with('tour')
                ->where('credit_status', TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS)
                ->latest()
                ->limit(8)
                ->get(['id', 'tour_id', 'travel_date', 'people', 'created_at', 'credit_status', 'visible_to_company', 'credits_required']),
            'heldBookingsCount' => (clone $bookingQuery)
                ->where('credit_status', TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS)
                ->count(),
            'creditMovements' => $company
                ? $company->creditMovements()
                    ->with(['booking', 'tour', 'creator'])
                    ->latest()
                    ->limit(8)
                    ->get()
                : collect(),
            'releasedBookings' => $company
                ? MonetizationAuditLog::query()
                    ->with(['booking', 'tour'])
                    ->where('company_id', $company->id)
                    ->where('event_type', MonetizationAuditLog::EVENT_BOOKING_RELEASED)
                    ->latest()
                    ->limit(8)
                    ->get()
                : collect(),
            'latestBookings' => (clone $visibleBookingQuery)
                ->with(['tour.images', 'tour.category', 'user'])
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
