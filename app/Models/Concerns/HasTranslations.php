<?php

namespace App\Models\Concerns;

use App\Support\LocaleManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTranslations
{
    public function translations(): HasMany
    {
        return $this->hasMany($this->translationModel());
    }

    public function translation(?string $locale = null): ?Model
    {
        $locale = LocaleManager::normalize($locale);

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale)
                ?: $this->translations->firstWhere('locale', LocaleManager::fallback());
        }

        return $this->translations()
            ->whereIn('locale', [$locale, LocaleManager::fallback()])
            ->orderByRaw('locale = ? desc', [$locale])
            ->first();
    }

    public function translated(string $attribute, ?string $locale = null, mixed $fallback = null): mixed
    {
        return $this->translation($locale)?->{$attribute}
            ?: $this->getRawOriginal($attribute)
            ?: $fallback;
    }

    public function scopeWithLocale(Builder $query, ?string $locale = null): Builder
    {
        $locale = LocaleManager::normalize($locale);

        return $query->with([
            'translations' => fn (HasMany $query) => $query->whereIn('locale', [$locale, LocaleManager::fallback()]),
        ]);
    }

    public function scopeWhereTranslationSlug(Builder $query, string $slug, ?string $locale = null): Builder
    {
        $locale = LocaleManager::normalize($locale);

        return $query->whereHas('translations', fn (Builder $query) => $query
            ->where('locale', $locale)
            ->where('slug', $slug));
    }

    public function syncTranslations(array $translations): void
    {
        foreach ($translations as $locale => $values) {
            $locale = LocaleManager::normalize((string) $locale);

            $this->translations()->updateOrCreate(
                ['locale' => $locale],
                collect($values)->filter(fn ($value): bool => $value !== null)->all(),
            );
        }
    }

    abstract protected function translationModel(): string;
}
