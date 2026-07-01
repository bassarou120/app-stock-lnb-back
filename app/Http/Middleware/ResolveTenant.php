<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->tenant_id) {
            app()->instance('currentTenantId', auth()->user()->tenant_id);
        }

        return $next($request);
    }
}