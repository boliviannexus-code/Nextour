<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->string('slug');
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->longText('includes')->nullable();
            $table->longText('excludes')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();

            $table->unique(['tour_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->index(['locale', 'title']);
        });

        Schema::create('category_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->unique(['category_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->index(['locale', 'name']);
        });

        Schema::create('activity_type_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_type_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->unique(['activity_type_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('guide_type_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guide_type_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->unique(['guide_type_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('transport_type_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transport_type_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->unique(['transport_type_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_type_translations');
        Schema::dropIfExists('guide_type_translations');
        Schema::dropIfExists('activity_type_translations');
        Schema::dropIfExists('category_translations');
        Schema::dropIfExists('tour_translations');
    }
};
