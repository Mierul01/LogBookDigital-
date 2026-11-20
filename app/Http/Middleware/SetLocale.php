<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** Languages the app has text for: English (default) and Bahasa Melayu. */
    public const LOCALES = ['en', 'ms'];

    /** Use the language picked with the EN / BM switch (saved in the "locale" cookie). */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale');

        if (in_array($locale, self::LOCALES, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
