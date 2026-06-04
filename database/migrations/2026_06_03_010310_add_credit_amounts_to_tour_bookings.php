<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_bookings', function (Blueprint $table): void {
            $table->unsignedInteger('credits_required')->default(0)->after('visible_to_company');
            $table->unsignedInteger('credits_consumed')->default(0)->after('credits_required');
        });
    }

    public function down(): void
    {
        Schema::table('tour_bookings', function (Blueprint $table): void {
            $table->dropColumn(['credits_required', 'credits_consumed']);
        });
    }
};
