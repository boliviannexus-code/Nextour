<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_bookings', function (Blueprint $table): void {
            $table->string('credit_status')->default('free')->after('status');
            $table->boolean('visible_to_company')->default(true)->after('credit_status');

            $table->index(['visible_to_company', 'credit_status']);
        });
    }

    public function down(): void
    {
        Schema::table('tour_bookings', function (Blueprint $table): void {
            $table->dropIndex(['visible_to_company', 'credit_status']);
            $table->dropColumn(['credit_status', 'visible_to_company']);
        });
    }
};
