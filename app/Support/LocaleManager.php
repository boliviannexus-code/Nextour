<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class LocaleManager
{
    public static function available(): array
    {
        return array_keys((array) config('locales.available', ['es' => [], 'en' => []]));
    }

    public static function fallback(): string
    {
        return (string) config('locales.fallback', 'es');
    }

    public static function normalize(?string $locale): string
    {
        $locale = Str::of((string) $locale)->before('-')->lower()->toString();

        return in_array($locale, self::available(), true) ? $locale : self::fallback();
    }

    public static function current(): string
    {
        return self::normalize(App::getLocale());
    }

    public static function preferredFrom(Request $request): string
    {
        $queryLocale = $request->query('lang');

        if (is_string($queryLocale) && $queryLocale !== '') {
            return self::normalize($queryLocale);
        }

        $sessionLocale = $request->session()->get('locale');

        if (is_string($sessionLocale) && $sessionLocale !== '') {
            return self::normalize($sessionLocale);
        }

        $cookieLocale = $request->cookie('locale');

        if (is_string($cookieLocale) && $cookieLocale !== '') {
            return self::normalize($cookieLocale);
        }

        return self::normalize($request->getPreferredLanguage(self::available()));
    }
}
