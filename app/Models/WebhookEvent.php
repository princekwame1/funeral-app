<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = [
        'provider',
        'event',
        'reference',
        'signature_ok',
        'response_status',
        'payload',
        'response_body',
        'error',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_ok' => 'boolean',
            'received_at' => 'datetime',
        ];
    }

    public function decodedPayload(): array
    {
        return json_decode($this->payload ?? '[]', true) ?: [];
    }
}
