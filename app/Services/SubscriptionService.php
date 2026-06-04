<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyCreditBalance;
use App\Models\CompanyCreditMovement;
use App\Models\CreditConsumptionRule;
use App\Models\MonetizationAuditLog;
use App\Models\SubscriptionSetting;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function getActiveConfiguration(): ?SubscriptionSetting
    {
        return SubscriptionSetting::query()->active()->first();
    }

    public function getCompanyBalance(Company $company): CompanyCreditBalance
    {
        return CompanyCreditBalance::query()->firstOrCreate([
            'company_id' => $company->id,
        ], [
            'credits_balance' => 0,
        ]);
    }

    public function addCredits(
        Company $company,
        int $credits,
        string $reason,
        ?User $createdBy = null,
        string $movementType = CompanyCreditMovement::TYPE_MANUAL_ADDITION,
        ?TourBooking $booking = null,
        ?Tour $tour = null,
    ): ?CompanyCreditMovement {
        return DB::transaction(function () use ($company, $credits, $reason, $createdBy, $movementType, $booking, $tour): CompanyCreditMovement {
            $balance = $this->lockedBalance($company);

            $balanceBefore = (int) $balance->credits_balance;
            $balanceAfter = $balanceBefore + $credits;
            $balance->update(['credits_balance' => $balanceAfter]);

            $movement = CompanyCreditMovement::query()->create([
                'company_id' => $company->id,
                'booking_id' => $booking?->id,
                'tour_id' => $tour?->id ?? $booking?->tour_id,
                'movement_type' => $movementType,
                'credits' => $credits,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => $reason,
                'created_by' => $createdBy?->id,
            ]);

            $this->recordAudit(
                eventType: MonetizationAuditLog::EVENT_CREDIT_ADDED,
                description: $reason,
                company: $company,
                booking: $booking,
                tour: $tour,
                createdBy: $createdBy,
                metadata: [
                    'credits' => $credits,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'movement_type' => $movementType,
                ],
            );

            if ($credits > 0) {
                $this->releaseHeldBookingsUsingLockedBalance($company, $balance, $createdBy);
            }

            return $movement;
        });
    }

    public function subtractCredits(
        Company $company,
        int $credits,
        string $reason,
        ?User $createdBy = null,
        string $movementType = CompanyCreditMovement::TYPE_MANUAL_SUBTRACTION,
        ?TourBooking $booking = null,
        ?Tour $tour = null,
    ): ?CompanyCreditMovement {
        return DB::transaction(function () use ($company, $credits, $reason, $createdBy, $movementType, $booking, $tour): CompanyCreditMovement {
            $balance = $this->lockedBalance($company);

            $balanceBefore = (int) $balance->credits_balance;

            if ($credits > $balanceBefore) {
                throw ValidationException::withMessages([
                    'credits' => 'La empresa no tiene creditos suficientes.',
                ]);
            }

            $balanceAfter = $balanceBefore - $credits;
            $balance->update(['credits_balance' => $balanceAfter]);

            $movement = CompanyCreditMovement::query()->create([
                'company_id' => $company->id,
                'booking_id' => $booking?->id,
                'tour_id' => $tour?->id ?? $booking?->tour_id,
                'movement_type' => $movementType,
                'credits' => -$credits,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => $reason,
                'created_by' => $createdBy?->id,
            ]);

            $this->recordAudit(
                eventType: MonetizationAuditLog::EVENT_CREDIT_SUBTRACTED,
                description: $reason,
                company: $company,
                booking: $booking,
                tour: $tour,
                createdBy: $createdBy,
                metadata: [
                    'credits' => $credits,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'movement_type' => $movementType,
                ],
            );

            if ($movementType === CompanyCreditMovement::TYPE_BOOKING_CONSUMPTION) {
                $this->recordAudit(
                    eventType: MonetizationAuditLog::EVENT_BOOKING_WITH_CREDIT,
                    description: $reason,
                    company: $company,
                    booking: $booking,
                    tour: $tour,
                    createdBy: $createdBy,
                    metadata: [
                        'credits' => $credits,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                    ],
                );
            }

            return $movement;
        });
    }

    public function recordCreditEvent(
        Company $company,
        string $reason,
        ?TourBooking $booking = null,
        ?Tour $tour = null,
        string $movementType = CompanyCreditMovement::TYPE_ADJUSTMENT,
        ?User $createdBy = null,
    ): CompanyCreditMovement {
        $balance = $this->getCompanyBalance($company);
        $currentBalance = (int) $balance->credits_balance;

        $movement = CompanyCreditMovement::query()->create([
            'company_id' => $company->id,
            'booking_id' => $booking?->id,
            'tour_id' => $tour?->id ?? $booking?->tour_id,
            'movement_type' => $movementType,
            'credits' => 0,
            'balance_before' => $currentBalance,
            'balance_after' => $currentBalance,
            'reason' => $reason,
            'created_by' => $createdBy?->id,
        ]);

        if ($booking?->credit_status === TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS) {
            $this->recordAudit(
                eventType: MonetizationAuditLog::EVENT_BOOKING_HELD,
                description: $reason,
                company: $company,
                booking: $booking,
                tour: $tour,
                createdBy: $createdBy,
                metadata: ['balance' => $currentBalance],
            );
        }

        return $movement;
    }

    public function creditsRequiredForTourPrice(float $tourPriceUsd): int
    {
        $rule = CreditConsumptionRule::query()
            ->active()
            ->where('min_tour_price', '<=', $tourPriceUsd)
            ->where(function ($query) use ($tourPriceUsd): void {
                $query->whereNull('max_tour_price')
                    ->orWhere('max_tour_price', '>=', $tourPriceUsd);
            })
            ->orderByDesc('min_tour_price')
            ->first();

        return max(1, (int) ($rule?->credits_required ?? $this->getActiveConfiguration()?->credits_per_booking ?? 1));
    }

    public function releaseHeldBookings(Company $company, ?User $createdBy = null): int
    {
        return DB::transaction(function () use ($company, $createdBy): int {
            $balance = $this->lockedBalance($company);

            return $this->releaseHeldBookingsUsingLockedBalance($company, $balance, $createdBy);
        });
    }

    public function recordAudit(
        string $eventType,
        string $description,
        ?Company $company = null,
        ?TourBooking $booking = null,
        ?Tour $tour = null,
        ?User $createdBy = null,
        array $metadata = [],
    ): MonetizationAuditLog {
        return MonetizationAuditLog::query()->create([
            'company_id' => $company?->id,
            'booking_id' => $booking?->id,
            'tour_id' => $tour?->id ?? $booking?->tour_id,
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'created_by' => $createdBy?->id,
        ]);
    }

    private function lockedBalance(Company $company): CompanyCreditBalance
    {
        $balance = CompanyCreditBalance::query()
            ->where('company_id', $company->id)
            ->lockForUpdate()
            ->first();

        if ($balance) {
            return $balance;
        }

        CompanyCreditBalance::query()->create([
            'company_id' => $company->id,
            'credits_balance' => 0,
        ]);

        return CompanyCreditBalance::query()
            ->where('company_id', $company->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function releaseHeldBookingsUsingLockedBalance(Company $company, CompanyCreditBalance $balance, ?User $createdBy): int
    {
        $released = 0;

        $heldBookings = TourBooking::query()
            ->where('credit_status', TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS)
            ->where('visible_to_company', false)
            ->whereHas('tour', fn ($query) => $query->where('company_id', $company->id))
            ->with('tour')
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($heldBookings as $booking) {
            $creditsPerBooking = max(1, (int) ($booking->credits_required ?: $this->getActiveConfiguration()?->credits_per_booking ?? 1));
            $currentBalance = (int) $balance->credits_balance;

            if ($currentBalance < $creditsPerBooking) {
                break;
            }

            $balanceAfter = $currentBalance - $creditsPerBooking;
            $balance->update(['credits_balance' => $balanceAfter]);
            $balance->refresh();

            $booking->update([
                'credit_status' => TourBooking::CREDIT_STATUS_CREDIT_CONSUMED,
                'visible_to_company' => true,
                'credits_consumed' => $creditsPerBooking,
            ]);

            CompanyCreditMovement::query()->create([
                'company_id' => $company->id,
                'booking_id' => $booking->id,
                'tour_id' => $booking->tour_id,
                'movement_type' => CompanyCreditMovement::TYPE_BOOKING_CONSUMPTION,
                'credits' => -$creditsPerBooking,
                'balance_before' => $currentBalance,
                'balance_after' => $balanceAfter,
                'reason' => 'Reserva liberada con creditos: '.$booking->booking_code.'.',
                'created_by' => $createdBy?->id,
            ]);

            $this->recordAudit(
                eventType: MonetizationAuditLog::EVENT_CREDIT_SUBTRACTED,
                description: 'Credito descontado por liberacion de reserva: '.$booking->booking_code.'.',
                company: $company,
                booking: $booking,
                tour: $booking->tour,
                createdBy: $createdBy,
                metadata: [
                    'credits' => $creditsPerBooking,
                    'balance_before' => $currentBalance,
                    'balance_after' => $balanceAfter,
                ],
            );

            $this->recordAudit(
                eventType: MonetizationAuditLog::EVENT_BOOKING_RELEASED,
                description: 'Reserva liberada con creditos: '.$booking->booking_code.'.',
                company: $company,
                booking: $booking,
                tour: $booking->tour,
                createdBy: $createdBy,
                metadata: [
                    'credits' => $creditsPerBooking,
                    'balance_before' => $currentBalance,
                    'balance_after' => $balanceAfter,
                ],
            );

            $released++;
        }

        return $released;
    }
}
