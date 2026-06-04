<?php

use App\Models\RegistrationRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('approval_status')->default(RegistrationRequest::STATUS_PENDING)->after('is_active');
            $table->timestamp('last_reviewed_at')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('last_reviewed_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();

            $table->index(['approval_status', 'created_at']);
        });

        Schema::table('registration_requests', function (Blueprint $table): void {
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('last_reviewed_at')->nullable()->after('submitted_at');
        });

        Schema::create('company_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status_before')->nullable();
            $table->string('status_after');
            $table->text('observation')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'reviewed_at']);
            $table->index(['status_after', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_reviews');

        Schema::table('registration_requests', function (Blueprint $table): void {
            $table->dropColumn(['submitted_at', 'last_reviewed_at']);
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['approval_status', 'created_at']);
            $table->dropColumn(['approval_status', 'last_reviewed_at', 'approved_at', 'approved_by']);
        });
    }
};
