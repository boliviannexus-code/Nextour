<?php

namespace App\Http\Middleware;

use App\Support\LocaleManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class PersistLocalePreference
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = LocaleManager::normalize($request->route('locale') ?: App::getLocale());

        App::setLocale($locale);
        URL::defaults(['locale' => $locale]);
        $request->session()->put('locale', $locale);

        $response = $next($request);
        $response->headers->setCookie(cookie('locale', $locale, 60 * 24 * 365));
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
