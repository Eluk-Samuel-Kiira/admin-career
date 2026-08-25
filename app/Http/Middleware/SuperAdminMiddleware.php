<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        // Check if user has super_admin role
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403, 'Access denied. Super Admin only.');
        }

        return $next($request);
    }
}