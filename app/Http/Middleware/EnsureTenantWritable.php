<?php

namespace App\Http\Middleware;

use App\Support\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantWritable
{
    public function __construct(private readonly CurrentTenant $current)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->current->get();
        // Super users acting outside a tenant context bypass this check.
        if (! $tenant) return $next($request);

        if ($tenant->isArchived()) {
            $archivedOn = $tenant->archived_at?->format('d M Y');
            abort(423, "This tenant was archived on {$archivedOn} and is now read-only.");
        }

        return $next($request);
    }
}
