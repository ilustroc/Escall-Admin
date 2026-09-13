<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $vite = app(Vite::class);
        $nonce = $vite->useCspNonce();
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set(
            'Referrer-Policy',
            'strict-origin-when-cross-origin',
        );
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=()',
        );
        $response->headers->set(
            'Content-Security-Policy',
            $this->contentSecurityPolicy($vite, $nonce),
        );
        $response->headers->remove('X-Powered-By');

        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age='.(int) config('security.headers.hsts_max_age').'; includeSubDomains',
            );
        }

        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        return $response;
    }

    private function contentSecurityPolicy(Vite $vite, string $nonce): string
    {
        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline'",
            "connect-src 'self'",
            "worker-src 'self' blob:",
        ];

        foreach ($this->viteDevelopmentSources($vite) as $source) {
            $directives[7] .= ' '.$source;
            $directives[8] .= ' '.$source;
            $directives[9] .= ' '.$source;
        }

        return implode('; ', $directives);
    }

    private function viteDevelopmentSources(Vite $vite): array
    {
        if (! $vite->isRunningHot() || ! File::exists($vite->hotFile())) {
            return [];
        }

        $url = trim(File::get($vite->hotFile()));
        $parts = parse_url($url);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return [];
        }

        $host = trim($parts['host'], '[]');
        $host = str_contains($host, ':') ? "[{$host}]" : $host;
        $authority = $host.(isset($parts['port']) ? ':'.$parts['port'] : '');
        $origin = $parts['scheme'].'://'.$authority;
        $webSocketScheme = $parts['scheme'] === 'https' ? 'wss' : 'ws';

        return [$origin, $webSocketScheme.'://'.$authority];
    }
}
