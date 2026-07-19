<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';

    public const METHOD_ONLINE = 'online';
    public const METHOD_OFFLINE = 'offline';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'donor_name',
        'phone',
        'amount',
        'currency',
        'payment_method',
        'status',
        'paystack_reference',
        'paystack_channel',
        'gateway_response',
        'sms_sent',
        'paid_at',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Donation $donation) {
            if (! $donation->tenant_id) {
                $donation->tenant_id = app(CurrentTenant::class)->id();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'sms_sent' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
