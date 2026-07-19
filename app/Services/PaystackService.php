<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaystackService
{
    private string $secret;
    private string $baseUrl;

    public function __construct()
    {
        $tenant = app(\App\Support\CurrentTenant::class)->get();
        $this->secret = (string) ($tenant?->paystack_secret ?: config('services.paystack.secret'));
        $this->baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
    }

    public function chargeMobileMoney(string $email, int $amountMinor, string $phone, string $provider = 'mtn', string $currency = 'GHS'): array
    {
        $reference = 'FDN-' . strtoupper(Str::random(12));

        $response = $this->client()->post($this->baseUrl . '/charge', [
            'email' => $email,
            'amount' => $amountMinor,
            'currency' => $currency,
            'reference' => $reference,
            'mobile_money' => [
                'phone' => $phone,
                'provider' => $provider,
            ],
        ]);

        return [
            'reference' => $reference,
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json() ?? [],
        ];
    }

    public function verify(string $reference): array
    {
        $response = $this->client()->get($this->baseUrl . '/transaction/verify/' . rawurlencode($reference));

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json() ?? [],
        ];
    }

    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        if (! $signature || ! $this->secret) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $rawBody, $this->secret), $signature);
    }

    private function client()
    {
        return Http::withToken($this->secret)->acceptJson()->asJson()->timeout(30);
    }
}
