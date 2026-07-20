<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BindCurrentTenant
{
    public function __construct(private readonly CurrentTenant $current)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Priority: subdomain → session tenant-switch (super) → user's tenant.
        $tenant = $this->fromSubdomain($request);

        if (! $tenant) {
            $user = $this->resolveUser($request);

            if ($user) {
                if ($user->isSuper()) {
                    // Sessions only exist for the web guard; skip for token API requests.
                    if ($request->hasSession()) {
                        $picked = $request->session()->get('super.active_tenant');
                        if ($picked && $t = Tenant::find($picked)) {
                            $tenant = $t;
                        }
                    }
                } elseif ($user->tenant_id) {
                    $tenant = Tenant::find($user->tenant_id);
                }
            }
        }

        if ($tenant) {
            $this->current->set($tenant);
        }

        return $next($request);
    }

    /**
     * Try every guard in turn so this middleware works for both web (session)
     * and API (Sanctum bearer token) authenticated requests. The `auth:sanctum`
     * route middleware runs AFTER group middleware, so we resolve manually here.
     */
    private function resolveUser(Request $request): ?User
    {
        // Fast path — default guard (session on web).
        $user = $request->user();
        if ($user) return $user;

        // Sanctum's personal-access-token guard, populated by Bearer header.
        foreach (['sanctum', 'api'] as $guard) {
            try {
                $u = Auth::guard($guard)->user();
                if ($u) return $u;
            } catch (\Throwable $e) {
                // Guard may not be configured — ignore.
            }
        }
        return null;
    }

    private function fromSubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $baseHosts = config('tenancy.base_hosts', ['localhost', '127.0.0.1']);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($appHost) $baseHosts[] = $appHost;

        // Reserved subdomains that should NOT resolve as tenant slugs.
        $reserved = ['www', 'app', 'admin', 'super', 'api', 'mail', 'static'];

        foreach ($baseHosts as $base) {
            if ($host === $base) return null;
            if (str_ends_with($host, '.' . $base)) {
                $sub = substr($host, 0, -strlen('.' . $base));
                if (in_array($sub, $reserved, true)) return null;
                return Tenant::query()->where('slug', $sub)->first();
            }
        }

        return null;
    }
}
