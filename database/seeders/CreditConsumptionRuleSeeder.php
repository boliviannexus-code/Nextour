<?php

namespace Database\Seeders;

use App\Models\CreditConsumptionRule;
use Illuminate\Database\Seeder;

class CreditConsumptionRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['min_tour_price' => 0, 'max_tour_price' => 50, 'credits_required' => 1],
            ['min_tour_price' => 50.01, 'max_tour_price' => 150, 'credits_required' => 2],
            ['min_tour_price' => 150.01, 'max_tour_price' => 300, 'credits_required' => 3],
            ['min_tour_price' => 300.01, 'max_tour_price' => null, 'credits_required' => 4],
        ];

        foreach ($rules as $rule) {
            CreditConsumptionRule::query()->updateOrCreate(
                [
                    'min_tour_price' => $rule['min_tour_price'],
                    'max_tour_price' => $rule['max_tour_price'],
                ],
                [
                    'credits_required' => $rule['credits_required'],
                    'is_active' => true,
                ],
            );
        }
    }
}
