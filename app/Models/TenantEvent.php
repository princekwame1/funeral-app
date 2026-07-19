<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantEvent extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'venue',
        'location_url',
        'description',
        'starts_at',
        'ends_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (TenantEvent $event) {
            if (! $event->tenant_id) {
                $event->tenant_id = app(CurrentTenant::class)->id();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isUpcoming(): bool
    {
        return $this->starts_at?->isFuture() ?? false;
    }
}
