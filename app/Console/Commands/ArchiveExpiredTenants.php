<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ArchiveExpiredTenants extends Command
{
    protected $signature = 'tenants:auto-archive';

    protected $description = 'Archive tenants whose archive_at date has passed. Archived tenants become read-only.';

    public function handle(): int
    {
        $today = Carbon::today();

        $due = Tenant::query()
            ->whereNull('archived_at')
            ->whereNotNull('archive_at')
            ->where('archive_at', '<=', $today)
            ->get();

        if ($due->isEmpty()) {
            $this->info('No tenants due for archival.');
            return self::SUCCESS;
        }

        foreach ($due as $tenant) {
            $tenant->update(['archived_at' => Carbon::now()]);
            $this->line("Archived #{$tenant->id} · {$tenant->name}");
        }

        $this->info("Archived {$due->count()} tenant(s).");
        return self::SUCCESS;
    }
}
