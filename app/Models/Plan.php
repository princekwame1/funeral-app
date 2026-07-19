<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'tagline',
        'sms_monthly',
        'donations_total',
        'price_ghs',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sms_monthly' => 'integer',
            'donations_total' => 'integer',
            'price_ghs' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Plan $p) {
            if (! $p->slug) {
                $p->slug = Str::slug($p->name) ?: 'plan-' . Str::random(6);
            }
        });
    }

    public function tenants(): HasMany
    {
        // Tenants store `plan` slug, not a plan_id.
        return $this->hasMany(Tenant::class, 'plan', 'slug');
    }
}
