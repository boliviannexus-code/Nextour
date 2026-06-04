<?php

namespace App\Services;

use App\Models\CompanyCreditMovement;
use App\Models\MonetizationAuditLog;
use App\Models\Tour;
use App\Models\TourAvailability;
use App\Models\TourBooking;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TourBookingService
{
    public function __construct(
        private readonly BookingLimitService $bookingLimits,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function quote(Tour $tour, string $date, int $people): array
    {
        $availability = $tour->availabilities()
            ->whereDate('date', $date)
            ->with('prices')
            ->first();

        $this->validateAvailability($tour, $availability, $people, $date);

        $unitPrice = $this->unitPrice($tour, $availability, $people);

        return [
            'availability' => $availability,
            'unit_price' => $unitPrice,
            'total' => $unitPrice * $people,
        ];
    }

    public function create(User $user, Tour $tour, array $data): TourBooking
    {
        return DB::transaction(function () use ($user, $tour, $data): TourBooking {
            $availability = $tour->availabilities()
                ->whereDate('date', $data['travel_date'])
                ->lockForUpdate()
                ->with('prices')
                ->first();

            $people = (int) $data['people'];
            $this->validateAvailability($tour, $availability, $people, $data['travel_date']);
            $unitPrice = $this->unitPrice($tour, $availability, $people);

            $booking = TourBooking::query()->create([
                'user_id' => $user->id,
                'tour_id' => $tour->id,
                'tour_availability_id' => $availability?->id,
                'booking_code' => $this->bookingCode(),
                'travel_date' => $data['travel_date'],
                'people' => $people,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'country' => $data['country'],
                'special_requirements' => $data['special_requirements'] ?? null,
                'unit_price_usd' => $unitPrice,
                'total_usd' => $unitPrice * $people,
                'status' => TourBooking::STATUS_CONFIRMED,
            ]);

            $this->applyCreditStatus($tour, $booking);

            $availability?->increment('booked_count', $people);

            if ($availability) {
                $this->markSoldOutIfCapacityReached($tour, $availability->refresh());
            }

            return $booking->load(['tour.images', 'tour.category', 'availability']);
        });
    }

    private function markSoldOutIfCapacityReached(Tour $tour, TourAvailability $availability): void
    {
        $capacity = $availability->capacity ?? $tour->capacity;

        if ($capacity !== null && $availability->booked_count >= $capacity) {
            $availability->update(['status' => TourAvailability::STATUS_SOLD_OUT]);
        }
    }

    private function applyCreditStatus(Tour $tour, TourBooking $booking): void
    {
        $company = $tour->company()->first();

        if (! $company) {
            $booking->update([
                'credit_status' => TourBooking::CREDIT_STATUS_FREE,
                'visible_to_company' => true,
            ]);

            return;
        }

        $configuration = $this->subscriptions->getActiveConfiguration();
        $dailyLimit = (int) ($configuration?->free_daily_pax_limit ?? 0);
        $weeklyLimit = (int) ($configuration?->free_weekly_pax_limit ?? 0);
        $dailyUsage = $this->bookingLimits->calculateDailyUsage($company, $booking->travel_date);
        $weeklyUsage = $this->bookingLimits->calculateWeeklyUsage($company, $booking->travel_date);
        $creditsNeeded = $this->subscriptions->creditsRequiredForTourPrice((float) $booking->unit_price_usd);
        $outsideFreeLimits = ($dailyLimit > 0 && $dailyUsage > $dailyLimit)
            || ($weeklyLimit > 0 && $weeklyUsage > $weeklyLimit);

        if (! $outsideFreeLimits) {
            $booking->update([
                'credit_status' => TourBooking::CREDIT_STATUS_FREE,
                'visible_to_company' => true,
                'credits_required' => 0,
                'credits_consumed' => 0,
            ]);

            $this->subscriptions->recordAudit(
                eventType: MonetizationAuditLog::EVENT_BOOKING_FREE,
                description: 'Reserva dentro del limite gratuito: '.$booking->booking_code.'.',
                company: $company,
                booking: $booking,
                tour: $tour,
                metadata: [
                    'daily_usage' => $dailyUsage,
                    'daily_limit' => $dailyLimit,
                    'weekly_usage' => $weeklyUsage,
                    'weekly_limit' => $weeklyLimit,
                ],
            );

            return;
        }

        try {
            $this->subscriptions->subtractCredits(
                company: $company,
                credits: $creditsNeeded,
                reason: 'Consumo de creditos por reserva '.$booking->booking_code.'.',
                movementType: CompanyCreditMovement::TYPE_BOOKING_CONSUMPTION,
                booking: $booking,
                tour: $tour,
            );

            $booking->update([
                'credit_status' => TourBooking::CREDIT_STATUS_CREDIT_CONSUMED,
                'visible_to_company' => true,
                'credits_required' => $creditsNeeded,
                'credits_consumed' => $creditsNeeded,
            ]);

            return;
        } catch (ValidationException) {
            //
        }

        $booking->update([
            'credit_status' => TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS,
            'visible_to_company' => false,
            'credits_required' => $creditsNeeded,
            'credits_consumed' => 0,
        ]);

        $this->subscriptions->recordCreditEvent(
            company: $company,
            reason: 'Reserva retenida por falta de creditos: '.$booking->booking_code.'.',
            booking: $booking,
            tour: $tour,
        );
    }

    private function validateAvailability(Tour $tour, ?TourAvailability $availability, int $people, string $date): void
    {
        if ($tour->status !== Tour::STATUS_ACTIVE || $tour->review_status !== Tour::REVIEW_APPROVED || ! $tour->bookings_enabled) {
            throw ValidationException::withMessages(['travel_date' => 'Este tour no esta disponible para reservas.']);
        }

        if (! $availability || $availability->status !== TourAvailability::STATUS_AVAILABLE) {
            throw ValidationException::withMessages(['travel_date' => 'La fecha seleccionada no esta disponible.']);
        }

        $capacity = $availability->capacity ?? $tour->capacity;
        $bookedCount = $availability->booked_count;

        if ($capacity !== null && ($capacity - $bookedCount) < $people) {
            throw ValidationException::withMessages(['people' => 'No hay cupos suficientes para la cantidad seleccionada.']);
        }
    }

    private function unitPrice(Tour $tour, ?TourAvailability $availability, int $people): float
    {
        $price = $availability ? $this->selectPriceForPeople($availability->prices, $people) : null;

        if (! $price) {
            $price = $this->selectPriceForPeople($tour->prices()->get(), $people);
        }

        if (! $price) {
            throw ValidationException::withMessages(['people' => 'No hay una tarifa configurada para esta cantidad de personas.']);
        }

        return (float) $price->price_usd;
    }

    private function bookedCount(Tour $tour, string $date): int
    {
        return (int) TourBooking::query()
            ->where('tour_id', $tour->id)
            ->whereDate('travel_date', $date)
            ->sum('people');
    }

    private function selectPriceForPeople(Collection $prices, int $people): mixed
    {
        $applicable = $prices
            ->filter(fn ($price): bool => $price->min_people <= $people && ($price->max_people === null || $price->max_people >= $people))
            ->sort($this->priceSorter());

        if ($applicable->isNotEmpty()) {
            return $applicable->first();
        }

        return $prices
            ->filter(fn ($price): bool => (int) $price->min_people === 1)
            ->sort($this->priceSorter())
            ->first();
    }

    private function priceSorter(): callable
    {
        return function ($first, $second): int {
            if ((int) $first->min_people !== (int) $second->min_people) {
                return (int) $second->min_people <=> (int) $first->min_people;
            }

            $firstMax = $first->max_people === null ? PHP_INT_MAX : (int) $first->max_people;
            $secondMax = $second->max_people === null ? PHP_INT_MAX : (int) $second->max_people;

            return $firstMax <=> $secondMax;
        };
    }

    private function bookingCode(): string
    {
        do {
            $code = 'TRV-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (TourBooking::query()->where('booking_code', $code)->exists());

        return $code;
    }
}
