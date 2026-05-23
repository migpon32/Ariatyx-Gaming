<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiTokenHasCompletedTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $user?->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor setup is required before using this endpoint.',
                'requires_two_factor_setup' => true,
            ], 403);
        }

        if ($token && $token->can('2fa:setup') && ! $token->can('*')) {
            return response()->json([
                'message' => 'Complete two-factor authentication before using this endpoint.',
                'requires_two_factor' => true,
            ], 403);
        }

        return $next($request);
    }
}
