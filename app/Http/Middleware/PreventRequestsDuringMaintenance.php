<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class PreventRequestsDuringMaintenance {
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/login',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // If app is NOT in maintenance → normal flow
        if (!app()->isDownForMaintenance()) {
            return $next($request);
        }

        // Always allow login route (GET + POST)
        if ($request->routeIs('login') || $request->is('login')) {
            return $next($request);
        }

        // Allow super admin (role_id = 1)
        if (Auth::check() && Auth::user()->role_id == 1) {
            return $next($request);
        }

        // Everything else is blocked
        return response()->view('errors.503', [], 503);
    }
}
