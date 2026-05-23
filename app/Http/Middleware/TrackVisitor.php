<?php

namespace App\Http\Middleware;

use App\Models\Visit;
use App\Support\GeoIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            $this->record($request);
        }

        return $response;
    }

    protected function shouldTrack(Request $request, Response $response): bool
    {
        if ($request->method() !== 'GET') {
            return false;
        }

        if ($response->isRedirection() || $response->isServerError()) {
            return false;
        }

        $path = $request->path();

        foreach (['admin', 'admin/*', 'install', 'install/*', 'livewire/*', '_debugbar/*'] as $p) {
            if ($request->is($p)) {
                return false;
            }
        }

        // Skip asset-ish requests just in case nginx didn't already serve them.
        if (preg_match('/\.(css|js|map|ico|png|jpe?g|gif|svg|webp|woff2?|ttf|eot)$/i', $path)) {
            return false;
        }

        $ua = (string) $request->userAgent();
        if (preg_match('/bot|spider|crawler|slurp|facebookexternalhit|pingdom|monitor/i', $ua)) {
            return false;
        }

        return true;
    }

    protected function record(Request $request): void
    {
        try {
            Visit::create([
                'session_id' => $request->hasSession() ? $request->session()->getId() : substr(sha1((string) $request->ip()), 0, 32),
                'ip' => (string) $request->ip(),
                'country' => GeoIp::country((string) $request->ip(), $request->header('CF-IPCountry')),
                'url' => substr($request->fullUrl(), 0, 1024),
                'referer' => $request->header('referer') ? substr($request->header('referer'), 0, 1024) : null,
                'user_agent_hash' => $request->userAgent() ? substr(sha1($request->userAgent()), 0, 64) : null,
                'visited_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let tracking break a real request.
            report($e);
        }
    }
}
