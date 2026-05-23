<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->two_factor_enabled) {
            return redirect()->route('two-factor.setup');
        }

        return $next($request);
    }
}
