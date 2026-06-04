<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Tour;
use App\Models\TourBooking;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class BookingLimitService
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function calculateDailyUsage(Company $company, CarbonInterface|string|null $date = null): int
    {
        $date = $this->asDate($date);

        return (int) $this->bookingsForCompany($company)
            ->whereDate('travel_date', $date)
            ->sum('people');
    }

    public function calculateWeeklyUsage(Company $company, CarbonInterface|string|null $date = null): int
    {
        $date = $this->asDate($date);
        $start = $date->subDays(7)->startOfDay();

        return (int) $this->bookingsForCompany($company)
            ->whereBetween('travel_date', [$start->toDateString(), $date->toDateString()])
            ->sum('people');
    }

    public function canPublishTour(Company $company, ?Tour $tour = null): bool
    {
        $configuration = $this->subscriptions->getActiveConfiguration();
        $limit = (int) ($configuration?->free_active_tours_limit ?? 0);

        if ($limit <= 0) {
            return false;
        }

        $publishedTours = $this->publishedToursQuery($company)
            ->when($tour?->exists, fn (Builder $query): Builder => $query->whereKeyNot($tour->getKey()))
            ->count();

        return $publishedTours < $limit;
    }

    public function isNearDailyLimit(Company $company, CarbonInterface|string|null $date = null): bool
    {
        $configuration = $this->subscriptions->getActiveConfiguration();
        $limit = (int) ($configuration?->free_daily_pax_limit ?? 0);

        return $this->isNearLimit($this->calculateDailyUsage($company, $date), $limit);
    }

    public function isNearWeeklyLimit(Company $company, CarbonInterface|string|null $date = null): bool
    {
        $configuration = $this->subscriptions->getActiveConfiguration();
        $limit = (int) ($configuration?->free_weekly_pax_limit ?? 0);

        return $this->isNearLimit($this->calculateWeeklyUsage($company, $date), $limit);
    }

    public function getCompanyStatus(Company $company, CarbonInterface|string|null $date = null): array
    {
        $configuration = $this->subscriptions->getActiveConfiguration();
        $balance = $this->subscriptions->getCompanyBalance($company);

        $publishedTours = $this->publishedToursQuery($company)->count();
        $tourLimit = (int) ($configuration?->free_active_tours_limit ?? 0);
        $dailyLimit = (int) ($configuration?->free_daily_pax_limit ?? 0);
        $weeklyLimit = (int) ($configuration?->free_weekly_pax_limit ?? 0);
        $dailyUsage = $this->calculateDailyUsage($company, $date);
        $weeklyUsage = $this->calculateWeeklyUsage($company, $date);

        return [
            'published_tours' => $publishedTours,
            'tour_limit' => $tourLimit,
            'daily_usage' => $dailyUsage,
            'daily_limit' => $dailyLimit,
            'weekly_usage' => $weeklyUsage,
            'weekly_limit' => $weeklyLimit,
            'credits_balance' => $balance->credits_balance,
            'credits_per_booking' => (int) ($configuration?->credits_per_booking ?? 1),
            'messages' => $this->messages($publishedTours, $tourLimit, $dailyUsage, $dailyLimit, $weeklyUsage, $weeklyLimit),
        ];
    }

    private function bookingsForCompany(Company $company): Builder
    {
        return TourBooking::query()
            ->where('status', '!=', TourBooking::STATUS_CANCELLED)
            ->whereHas('tour', fn (Builder $query): Builder => $query->where('company_id', $company->id));
    }

    private function publishedToursQuery(Company $company): Builder
    {
        return Tour::query()
            ->where('company_id', $company->id)
            ->where(function (Builder $query): void {
                $query
                    ->where('status', 'published')
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where('status', Tour::STATUS_ACTIVE)
                            ->where('review_status', Tour::REVIEW_APPROVED);
                    });
            });
    }

    private function messages(int $publishedTours, int $tourLimit, int $dailyUsage, int $dailyLimit, int $weeklyUsage, int $weeklyLimit): array
    {
        return array_values(array_filter([
            $this->messageForLimit('Tours publicados', $publishedTours, $tourLimit),
            $this->messageForLimit('Reservas de hoy', $dailyUsage, $dailyLimit),
            $this->messageForLimit('Reservas de los ultimos 7 dias', $weeklyUsage, $weeklyLimit),
        ]));
    }

    private function messageForLimit(string $label, int $usage, int $limit): ?string
    {
        if ($limit <= 0) {
            return null;
        }

        $percentage = (int) floor(($usage / $limit) * 100);
        $threshold = $this->reachedThreshold($percentage);

        if ($threshold === null) {
            return null;
        }

        return "{$label}: {$usage}/{$limit} ({$threshold}% del limite).";
    }

    private function isNearLimit(int $usage, int $limit): bool
    {
        if ($limit <= 0) {
            return false;
        }

        return $this->reachedThreshold((int) floor(($usage / $limit) * 100)) !== null;
    }

    private function reachedThreshold(int $percentage): ?int
    {
        return collect($this->warningThresholds())
            ->filter(fn (int $threshold): bool => $percentage >= $threshold)
            ->max();
    }

    private function warningThresholds(): array
    {
        $configuration = $this->subscriptions->getActiveConfiguration();
        $thresholds = $configuration?->warning_thresholds ?: config('subscriptions.warning_thresholds', [50, 75, 90, 100]);

        return collect($thresholds)
            ->map(fn (mixed $threshold): int => (int) $threshold)
            ->filter(fn (int $threshold): bool => $threshold > 0 && $threshold <= 100)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function asDate(CarbonInterface|string|null $date): CarbonImmutable
    {
        if ($date instanceof CarbonInterface) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        if (is_string($date)) {
            return CarbonImmutable::parse($date)->startOfDay();
        }

        return CarbonImmutable::now()->startOfDay();
    }
}
