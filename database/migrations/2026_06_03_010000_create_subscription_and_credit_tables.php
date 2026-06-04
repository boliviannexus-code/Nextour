<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('free_active_tours_limit')->default(2);
            $table->unsignedInteger('free_daily_pax_limit')->default(5);
            $table->unsignedInteger('free_weekly_pax_limit')->default(20);
            $table->unsignedInteger('daily_warning_threshold')->default(5);
            $table->unsignedInteger('weekly_warning_threshold')->default(20);
            $table->unsignedInteger('credits_per_booking')->default(1);
            $table->decimal('credit_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        $this->createActiveSubscriptionSettingIndex();

        Schema::create('company_credit_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->integer('credits_balance')->default(0);
            $table->timestamps();

            $table->unique('company_id');
        });

        Schema::create('company_credit_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('tour_bookings')->nullOnDelete();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->nullOnDelete();
            $table->string('movement_type');
            $table->integer('credits');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['movement_type', 'created_at']);
            $table->index(['booking_id', 'movement_type']);
            $table->index(['tour_id', 'movement_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_credit_movements');
        Schema::dropIfExists('company_credit_balances');

        $this->dropActiveSubscriptionSettingIndex();
        Schema::dropIfExists('subscription_settings');
    }

    private function createActiveSubscriptionSettingIndex(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX subscription_settings_only_one_active ON subscription_settings (is_active) WHERE is_active = true');
        }

        if ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX subscription_settings_only_one_active ON subscription_settings (is_active) WHERE is_active = 1');
        }
    }

    private function dropActiveSubscriptionSettingIndex(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS subscription_settings_only_one_active');
        }

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS subscription_settings_only_one_active');
        }
    }
};
