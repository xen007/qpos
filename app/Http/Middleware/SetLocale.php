<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the language saved for this browser session.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale', 'fr'));
        $supportedLocales = ['fr', 'en'];

        if (! in_array($locale, $supportedLocales, true)) {
            $fallbackLocale = config('app.fallback_locale', 'fr');
            $locale = in_array($fallbackLocale, $supportedLocales, true) ? $fallbackLocale : 'fr';
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
