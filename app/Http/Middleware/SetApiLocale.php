<?php

namespace App\Http\Middleware;

use App\Support\LocaleManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = LocaleManager::normalize($request->query('lang') ?: $request->header('Accept-Language'));

        App::setLocale($locale);

        return $next($request)->headers->set('Content-Language', $locale);
    }
}
