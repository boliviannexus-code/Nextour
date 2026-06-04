<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('credits_amount');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_qr_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('credit_consumption_rules', function (Blueprint $table): void {
            $table->id();
            $table->decimal('min_tour_price', 10, 2);
            $table->decimal('max_tour_price', 10, 2)->nullable();
            $table->unsignedInteger('credits_required');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'min_tour_price', 'max_tour_price']);
        });

        Schema::create('credit_purchase_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_package_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('requested_credits');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_proof_path');
            $table->string('status')->default('pending');
            $table->text('admin_observation')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_purchase_requests');
        Schema::dropIfExists('credit_consumption_rules');
        Schema::dropIfExists('credit_packages');
    }
};
