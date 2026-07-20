<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'contact_email',
        'contact_phone',
        'tagline',
        'family_name',
        'deceased_name',
        'deceased_date_of_birth',
        'deceased_date_of_passing',
        'brand_primary',
        'brand_accent',
        'logo_url',
        'splash_image_url',
        'background_image_url',
        'favicon_url',
        'sms_sender_id',
        'thankyou_template',
        'paystack_secret',
        'paystack_public',
        'is_active',
        'plan',
        'sms_limit_monthly',
        'donation_limit_total',
        'archive_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deceased_date_of_birth' => 'date',
            'deceased_date_of_passing' => 'date',
            'archive_at' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    protected $hidden = [
        'paystack_secret',
    ];

    public static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (! $tenant->slug) {
                $tenant->slug = Str::slug($tenant->name) ?: 'tenant-' . Str::random(6);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function smsCampaigns(): HasMany
    {
        return $this->hasMany(SmsCampaign::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TenantEvent::class);
    }
}
