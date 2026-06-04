<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyCreditBalance;
use App\Models\CompanyCreditMovement;
use App\Models\MonetizationAuditLog;
use App\Models\SubscriptionSetting;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_credit_addition_registers_movement_and_audit(): void
    {
        $this->activeSetting();
        $company = Company::factory()->create();
        $admin = User::factory()->create();

        app(SubscriptionService::class)->addCredits(
            company: $company,
            credits: 5,
            reason: 'Carga manual de prueba.',
            createdBy: $admin,
        );

        $this->assertDatabaseHas('company_credit_balances', [
            'company_id' => $company->id,
            'credits_balance' => 5,
        ]);
        $this->assertDatabaseHas('company_credit_movements', [
            'company_id' => $company->id,
            'movement_type' => CompanyCreditMovement::TYPE_MANUAL_ADDITION,
            'credits' => 5,
            'balance_before' => 0,
            'balance_after' => 5,
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('monetization_audit_logs', [
            'company_id' => $company->id,
            'event_type' => MonetizationAuditLog::EVENT_CREDIT_ADDED,
            'created_by' => $admin->id,
        ]);
    }

    public function test_credit_addition_releases_oldest_held_bookings_while_balance_exists(): void
    {
        $this->activeSetting(['credits_per_booking' => 1]);
        $company = Company::factory()->create();
        $tour = $this->tour($company);
        $oldest = $this->heldBooking($tour, 'TRV-OLD', now()->subHours(3));
        $middle = $this->heldBooking($tour, 'TRV-MID', now()->subHours(2));
        $newest = $this->heldBooking($tour, 'TRV-NEW', now()->subHour());

        app(SubscriptionService::class)->addCredits(
            company: $company,
            credits: 2,
            reason: 'Carga con liberacion.',
        );

        $this->assertSame(TourBooking::CREDIT_STATUS_CREDIT_CONSUMED, $oldest->fresh()->credit_status);
        $this->assertTrue($oldest->fresh()->visible_to_company);
        $this->assertSame(TourBooking::CREDIT_STATUS_CREDIT_CONSUMED, $middle->fresh()->credit_status);
        $this->assertTrue($middle->fresh()->visible_to_company);
        $this->assertSame(TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS, $newest->fresh()->credit_status);
        $this->assertFalse($newest->fresh()->visible_to_company);

        $this->assertDatabaseHas('company_credit_balances', [
            'company_id' => $company->id,
            'credits_balance' => 0,
        ]);
        $this->assertSame(2, MonetizationAuditLog::query()
            ->where('company_id', $company->id)
            ->where('event_type', MonetizationAuditLog::EVENT_BOOKING_RELEASED)
            ->count());
    }

    public function test_release_uses_configured_credits_per_booking(): void
    {
        $this->activeSetting(['credits_per_booking' => 2]);
        $company = Company::factory()->create();
        $tour = $this->tour($company);
        $first = $this->heldBooking($tour, 'TRV-FIRST', now()->subHours(2));
        $second = $this->heldBooking($tour, 'TRV-SECOND', now()->subHour());

        CompanyCreditBalance::query()->create([
            'company_id' => $company->id,
            'credits_balance' => 3,
        ]);

        $released = app(SubscriptionService::class)->releaseHeldBookings($company);

        $this->assertSame(1, $released);
        $this->assertSame(TourBooking::CREDIT_STATUS_CREDIT_CONSUMED, $first->fresh()->credit_status);
        $this->assertSame(TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS, $second->fresh()->credit_status);
        $this->assertDatabaseHas('company_credit_balances', [
            'company_id' => $company->id,
            'credits_balance' => 1,
        ]);
    }

    private function activeSetting(array $overrides = []): SubscriptionSetting
    {
        return SubscriptionSetting::query()->create(array_merge([
            'free_active_tours_limit' => 2,
            'free_daily_pax_limit' => 5,
            'free_weekly_pax_limit' => 20,
            'daily_warning_threshold' => 100,
            'weekly_warning_threshold' => 100,
            'warning_thresholds' => [50, 75, 90, 100],
            'credits_per_booking' => 1,
            'credit_price' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function tour(Company $company): Tour
    {
        return Tour::query()->create([
            'company_id' => $company->id,
            'category_id' => Category::factory()->create()->id,
            'name' => 'Tour monetizado',
            'title' => 'Tour monetizado',
            'status' => Tour::STATUS_ACTIVE,
            'review_status' => Tour::REVIEW_APPROVED,
            'bookings_enabled' => true,
        ]);
    }

    private function heldBooking(Tour $tour, string $code, mixed $createdAt): TourBooking
    {
        $tourist = User::factory()->create();

        return TourBooking::query()->create([
            'user_id' => $tourist->id,
            'tour_id' => $tour->id,
            'booking_code' => $code,
            'travel_date' => now()->addDays(2)->toDateString(),
            'people' => 2,
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'email' => strtolower($code).'@example.com',
            'phone' => '70000000',
            'country' => 'Bolivia',
            'unit_price_usd' => 10,
            'total_usd' => 20,
            'status' => TourBooking::STATUS_CONFIRMED,
            'credit_status' => TourBooking::CREDIT_STATUS_HELD_FOR_CREDITS,
            'visible_to_company' => false,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
