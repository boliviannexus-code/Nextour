<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('website')->nullable()->after('email');
            $table->text('description')->nullable()->after('website');
            $table->string('tax_document_path')->nullable()->after('logo_path');
            $table->string('legal_representative_first_name')->nullable()->after('tax_document_path');
            $table->string('legal_representative_last_name')->nullable()->after('legal_representative_first_name');
            $table->string('legal_representative_document_number')->nullable()->after('legal_representative_last_name');
            $table->string('legal_representative_document_type')->nullable()->after('legal_representative_document_number');
        });

        Schema::create('independent_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('document_number');
            $table->string('phone');
            $table->string('email');
            $table->string('address')->nullable();
            $table->string('work_type');
            $table->text('description')->nullable();
            $table->boolean('is_certified_guide')->default(false);
            $table->string('id_front_path');
            $table->string('id_back_path');
            $table->string('profile_photo_path');
            $table->timestamps();

            $table->index(['user_id', 'work_type']);
        });

        Schema::create('registration_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('independent_profile_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_requests');
        Schema::dropIfExists('independent_profiles');

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn([
                'website',
                'description',
                'tax_document_path',
                'legal_representative_first_name',
                'legal_representative_last_name',
                'legal_representative_document_number',
                'legal_representative_document_type',
            ]);
        });
    }
};
