<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createCatalog('packages', ['price', 'duration', 'image', 'status']);
        $this->createTranslation('package_translations', 'package_id', ['title', 'slug', 'short_description', 'description', 'includes', 'recommendations']);

        $this->createCatalog('accommodations', ['city', 'address', 'image', 'status']);
        $this->createTranslation('accommodation_translations', 'accommodation_id', ['title', 'slug', 'short_description', 'description', 'amenities', 'policies']);

        $this->createCatalog('rooms', ['accommodation_id', 'price', 'capacity', 'image', 'status']);
        $this->createTranslation('room_translations', 'room_id', ['title', 'slug', 'description', 'amenities']);

        $this->createCatalog('services', ['icon', 'status']);
        $this->createTranslation('service_translations', 'service_id', ['title', 'slug', 'description']);

        $this->createCatalog('faqs', ['sort_order', 'status']);
        $this->createTranslation('faq_translations', 'faq_id', ['question', 'slug', 'answer']);

        $this->createCatalog('policies', ['type', 'status']);
        $this->createTranslation('policy_translations', 'policy_id', ['title', 'slug', 'body']);

        $this->createCatalog('blog_posts', ['image', 'status', 'published_at']);
        $this->createTranslation('blog_post_translations', 'blog_post_id', ['title', 'slug', 'excerpt', 'body', 'meta_title', 'meta_description']);
    }

    public function down(): void
    {
        foreach (['blog_post', 'policy', 'faq', 'service', 'room', 'accommodation', 'package'] as $entity) {
            Schema::dropIfExists($entity.'_translations');
        }

        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('policies');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('services');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('accommodations');
        Schema::dropIfExists('packages');
    }

    private function createCatalog(string $table, array $columns): void
    {
        Schema::create($table, function (Blueprint $table) use ($columns): void {
            $table->id();

            foreach ($columns as $column) {
                match ($column) {
                    'price' => $table->decimal($column, 10, 2)->nullable(),
                    'capacity' => $table->unsignedInteger($column)->nullable(),
                    'sort_order' => $table->unsignedInteger($column)->default(0),
                    'published_at' => $table->timestamp($column)->nullable(),
                    'accommodation_id' => $table->foreignId($column)->nullable()->constrained()->nullOnDelete(),
                    default => $table->string($column)->nullable(),
                };
            }

            $table->timestamps();
            $table->index('status');
        });
    }

    private function createTranslation(string $tableName, string $foreignKey, array $columns): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($foreignKey, $columns): void {
            $table->id();
            $table->foreignId($foreignKey)->constrained(str($foreignKey)->beforeLast('_id')->plural()->toString())->cascadeOnDelete();
            $table->string('locale', 5);

            foreach ($columns as $column) {
                match ($column) {
                    'slug', 'title', 'question', 'meta_title' => $table->string($column),
                    'short_description', 'excerpt', 'meta_description' => $table->text($column)->nullable(),
                    default => $table->longText($column)->nullable(),
                };
            }

            $table->unique([$foreignKey, 'locale']);
            $table->unique(['locale', 'slug']);
        });
    }
};
