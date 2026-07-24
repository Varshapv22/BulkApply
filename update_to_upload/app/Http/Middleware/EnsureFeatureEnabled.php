<?php

namespace App\Http\Middleware;

use App\Models\FeatureFlag;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        if (!FeatureFlag::enabled($key)) {
            abort(403, 'This feature is currently disabled by the administrator.');
        }

        return $next($request);
    }
}
