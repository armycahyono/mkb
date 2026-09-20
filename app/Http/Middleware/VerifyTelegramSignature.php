<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramSignature
{
    /**
     * Handle an incoming request.
     *
     * Verifies the Telegram webhook request using the secret token header.
     * If the secret token is not configured, falls back to an IP whitelist.
     * Returns 403 Forbidden when verification fails.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = env('TELEGRAM_WEBHOOK_SECRET');
        $provided = $request->header('X-Telegram-Bot-Api-Secret-Token');

        // If a secret token is configured, enforce it.
        if ($secret) {
            if ($provided !== $secret) {
                Log::warning('Telegram webhook secret token mismatch.');
                abort(403, 'Forbidden');
            }

            return $next($request);
        }

        // No secret token set – fallback to IP whitelist.
        $allowedIps = [
            // Example Telegram IP ranges (replace with actual ranges as needed)
            '149.154.160.0/20',
            '91.108.4.0/22',
        ];
        $clientIp = $request->ip();
        foreach ($allowedIps as $cidr) {
            if ($this->ipInCidr($clientIp, $cidr)) {
                return $next($request);
            }
        }
        Log::warning('Telegram webhook request from unauthorized IP: '.$clientIp);
        abort(403, 'Forbidden');
    }

    /**
     * Determine if an IP is within a CIDR block.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$range, $netmask] = explode('/', $cidr);
        $ipDecimal = ip2long($ip);
        $rangeDecimal = ip2long($range);
        $mask = -1 << (32 - (int) $netmask);
        $rangeDecimal &= $mask; // network address

        return ($ipDecimal & $mask) === $rangeDecimal;
    }
}
