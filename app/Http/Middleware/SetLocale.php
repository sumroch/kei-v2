<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Admin UI language follows users.lang (ja / en). Guests get Japanese. */
class SetLocale
{
    public const LOCALES = ['ja', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->lang;

        app()->setLocale(in_array($locale, self::LOCALES, true) ? $locale : 'ja');

        return $next($request);
    }
}
