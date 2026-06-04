<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_settings', function (Blueprint $table): void {
            $table->json('warning_thresholds')->nullable()->after('weekly_warning_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_settings', function (Blueprint $table): void {
            $table->dropColumn('warning_thresholds');
        });
    }
};
