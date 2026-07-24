<?php

namespace App\Http\Middleware;

use App\Models\IpRule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceIpRules
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        foreach (IpRule::blockedIps() as $rule) {
            if ($this->matches($ip, $rule)) {
                abort(403, 'Access from your IP address has been blocked.');
            }
        }

        return $next($request);
    }

    private function matches(string $ip, string $rule): bool
    {
        if (!str_contains($rule, '/')) {
            return $ip === $rule;
        }

        [$subnet, $bits] = explode('/', $rule, 2);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
