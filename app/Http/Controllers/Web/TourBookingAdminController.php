<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TourBooking;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TourBookingAdminController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('bookings.view'), 403);

        $filters = $request->only(['status', 'q', 'date_from', 'date_to', 'visibility', 'credit_status']);
        $isGlobalAdmin = CompanyContext::isGlobalAdmin($request->user());

        $bookings = TourBooking::query()
            ->with(['tour.company', 'tour.category', 'user'])
            ->whereHas('tour', fn (Builder $query): Builder => CompanyContext::scope($query, $request->user()))
            ->when(! $isGlobalAdmin, fn ($query) => $query->where('visible_to_company', true))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(($filters['credit_status'] ?? null) && $isGlobalAdmin, fn ($query, string $status) => $query->where('credit_status', $status))
            ->when(($filters['visibility'] ?? null) && $isGlobalAdmin, function ($query, string $visibility): void {
                if ($visibility === 'visible') {
                    $query->where('visible_to_company', true);
                }

                if ($visibility === 'held') {
                    $query->where('visible_to_company', false);
                }
            })
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('travel_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('travel_date', '<=', $date))
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('booking_code', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('tour', fn ($tourQuery) => $tourQuery->where('title', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'statuses' => TourBooking::STATUSES,
            'creditStatuses' => TourBooking::CREDIT_STATUSES,
            'isGlobalAdmin' => $isGlobalAdmin,
        ]);
    }

    public function show(Request $request, TourBooking $booking): View
    {
        $booking->load(['tour.company', 'tour.category', 'tour.guideType', 'availability', 'user', 'creditMovements.creator']);
        abort_unless(
            $request->user()?->can('bookings.view')
            && CompanyContext::belongsToUser($booking->tour?->company_id, $request->user()),
            403
        );
        abort_if(! CompanyContext::isGlobalAdmin($request->user()) && ! $booking->visible_to_company, 403);

        return view('bookings.show', [
            'booking' => $booking,
            'statuses' => TourBooking::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, TourBooking $booking): RedirectResponse
    {
        $booking->loadMissing('tour');
        abort_unless(
            $request->user()?->can('bookings.manage')
            && CompanyContext::belongsToUser($booking->tour?->company_id, $request->user()),
            403
        );
        abort_if(! CompanyContext::isGlobalAdmin($request->user()) && ! $booking->visible_to_company, 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(TourBooking::STATUSES))],
        ]);

        $booking->update(['status' => $validated['status']]);

        return back()->with('success', 'Estado de reserva actualizado correctamente.');
    }
}
