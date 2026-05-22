<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolve($request);

        if (! $tenant) {
            abort(404, 'Shop not found.');
        }

        if (! $tenant->is_active) {
            abort(503, 'Shop temporarily unavailable.');
        }

        app()->instance('currentTenant', $tenant);
        app()->setLocale($tenant->locale);
        config(['app.currency' => $tenant->currency]);

        return $next($request);
    }

    protected function resolve(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $baseDomain = config('shop.base_domain');

        if ($baseDomain && str_ends_with($host, '.'.$baseDomain)) {
            $slug = substr($host, 0, -strlen('.'.$baseDomain));

            return Tenant::where('slug', $slug)->first();
        }

        return Tenant::where('domain', $host)->first();
    }
}
