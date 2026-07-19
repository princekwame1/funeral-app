<?php

use App\Models\Tenant;

if (! function_exists('tenant_public_host')) {
    /**
     * Return the public subdomain host for this tenant using the first non-local
     * base host in tenancy config, falling back to the APP_URL host.
     */
    function tenant_public_host(Tenant $tenant): string
    {
        $hosts = config('tenancy.base_hosts', []);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        // Prefer a domain that's not localhost/127.0.0.1 for the "shareable" URL.
        $primary = $appHost;
        foreach ($hosts as $h) {
            if (! in_array($h, ['localhost', '127.0.0.1'], true) && ! str_starts_with($h, '.')) {
                $primary = $h;
                break;
            }
        }
        return $tenant->slug . '.' . $primary;
    }
}

if (! function_exists('tenant_public_url')) {
    function tenant_public_url(Tenant $tenant): string
    {
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https';
        return $scheme . '://' . tenant_public_host($tenant);
    }
}
