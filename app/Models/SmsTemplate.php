<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsTemplate extends Model
{
    // Message kinds
    public const KIND_THANKYOU = 'thankyou';
    public const KIND_NOTIFICATIONS = 'notifications';
    public const KIND_INVITATIONS = 'invitations';
    public const KIND_POST = 'post';

    public const KINDS = [
        self::KIND_THANKYOU => 'Payment thank-you (auto-sent)',
        self::KIND_NOTIFICATIONS => 'General notifications',
        self::KIND_INVITATIONS => 'Funeral invitations',
        self::KIND_POST => 'Post-funeral messages',
    ];

    protected $fillable = [
        'tenant_id',
        'kind',
        'slug',
        'label',
        'body',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (SmsTemplate $t) {
            if (! $t->tenant_id) {
                $t->tenant_id = app(CurrentTenant::class)->id();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
