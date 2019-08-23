<?php

namespace JosephNC\Translation\Middleware;

use Closure;
use Illuminate\Http\Request;
use JosephNC\Translation\Facades\Translation;

class LocaleMiddleware
{
    /**
     * Sets the locale cookie on every request depending
     * on the locale supplied in the route prefix.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $lang = Translation::getRoutePrefix() ?? '';
        $lang = empty( $lang ) ? Translation::getLocale() : $lang;

        Translation::setLocale( $lang );

        return $next($request);
    }
}
