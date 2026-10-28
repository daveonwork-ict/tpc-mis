<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next, $permission = null)
    {
        $user = Auth::user();

        if (! $user) {
            return abort(403, 'Access Denied');
        }

        // 1. Super Admin (role_id 1)
        if ($user->role_id === 1) {
            return $next($request);
        }

        // 2. Roles 6 & 7 with role-based permissions
        if (in_array($user->role_id, [6, 7]) &&
            in_array($permission, $user->role->permissions ?? [])
        ) {
            return $next($request);
        }

        // 3. User-specific permissions
        if (in_array($permission, $user->permissions ?? [])) {
            return $next($request);
        }

        // Default: Access denied
        return abort(403, 'Access Denied');
    }
}
