<?php

namespace Database\Seeders;

use App\Models\SubscriptionSetting;
use Illuminate\Database\Seeder;

class SubscriptionSettingSeeder extends Seeder
{
    public function run(): void
    {
        $setting = SubscriptionSetting::query()->active()->first()
            ?? SubscriptionSetting::query()->oldest('id')->first()
            ?? new SubscriptionSetting;

        $setting->fill([
            'free_active_tours_limit' => 2,
            'free_daily_pax_limit' => 5,
            'free_weekly_pax_limit' => 20,
            'daily_warning_threshold' => 5,
            'weekly_warning_threshold' => 20,
            'warning_thresholds' => [50, 75, 90, 100],
            'credits_per_booking' => 1,
            'credit_price' => 0,
            'is_active' => true,
        ])->save();

        SubscriptionSetting::query()
            ->whereKeyNot($setting->id)
            ->update(['is_active' => false]);
    }
}
