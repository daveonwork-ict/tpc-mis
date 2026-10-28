<?php

namespace Modules\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceLoginRestrictionMiddleware
{
    /**
     * Handle an incoming request.
     */

    public function handle(Request $request, Closure $next): Response
    {
        // If application is NOT in maintenance mode → allow everything
        if (!app()->isDownForMaintenance()) {
            return $next($request);
        }

        // Always allow login routes (GET + POST)
        if ($request->routeIs('login') || $request->is('login')) {
            return $next($request);
        }

        // Allow super admin during maintenance
        if (Auth::check() && Auth::user()->role_id == 1) {
            return $next($request);
        }

        // Block everything else
        abort(503, 'System is under maintenance.');
    }
}
