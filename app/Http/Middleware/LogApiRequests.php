<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        DB::table('api_request_logs')->insert([
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            'user_id' => $request->user()?->id,
            'created_at' => now(),
        ]);

        return $response;
    }
}
