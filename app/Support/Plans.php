<?php

namespace App\Support;

use App\Models\Donation;
use App\Models\Plan;
use App\Models\SmsCampaign;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class Plans
{
    public const FREE = 'free';
    public const STARTER = 'starter';
    public const PRO = 'pro';

    /** Hardcoded fallback used if the plans table isn't ready yet. */
    private const FALLBACK = [
        self::FREE => ['name' => 'Free', 'sms_monthly' => 100, 'donations_total' => 500, 'price_ghs' => 0, 'tagline' => 'Perfect for small family gatherings.'],
        self::STARTER => ['name' => 'Starter', 'sms_monthly' => 1000, 'donations_total' => 5000, 'price_ghs' => 100, 'tagline' => 'Room to grow for medium-sized funerals.'],
        self::PRO => ['name' => 'Pro', 'sms_monthly' => null, 'donations_total' => null, 'price_ghs' => 500, 'tagline' => 'Unlimited SMS and donations. No caps.'],
    ];

    private static ?array $cache = null;

    /**
     * Returns all active plans as [slug => [name, sms_monthly, donations_total, price_ghs, tagline]].
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        try {
            $rows = Plan::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
            if ($rows->isNotEmpty()) {
                $map = [];
                foreach ($rows as $p) {
                    $map[$p->slug] = [
                        'name' => $p->name,
                        'sms_monthly' => $p->sms_monthly,
                        'donations_total' => $p->donations_total,
                        'price_ghs' => $p->price_ghs,
                        'tagline' => $p->tagline,
                    ];
                }
                self::$cache = $map;
                return $map;
            }
        } catch (\Throwable $e) {
            // Fall through to fallback (e.g. table doesn't exist during migrate)
        }
        self::$cache = self::FALLBACK;
        return self::FALLBACK;
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * @deprecated Prefer Plans::all(). Kept for BC where existing views iterate DEFINITIONS.
     */
    public static function definitions(): array
    {
        return self::all();
    }

    public static function definition(?string $plan): array
    {
        $all = self::all();
        return $all[$plan] ?? ($all[self::FREE] ?? self::FALLBACK[self::FREE]);
    }

    public static function limits(Tenant $tenant): array
    {
        $def = self::definition($tenant->plan);
        return [
            'sms_monthly' => $tenant->sms_limit_monthly ?? $def['sms_monthly'],
            'donations_total' => $tenant->donation_limit_total ?? $def['donations_total'],
        ];
    }

    public static function usage(Tenant $tenant): array
    {
        $monthStart = Carbon::now()->startOfMonth();

        $smsThisMonth = (int) SmsCampaign::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $monthStart)
            ->sum('sent_count');

        $donationsTotal = (int) Donation::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        return [
            'sms_monthly' => $smsThisMonth,
            'donations_total' => $donationsTotal,
        ];
    }

    public static function usageRatio(Tenant $tenant): array
    {
        $limits = self::limits($tenant);
        $usage = self::usage($tenant);
        return [
            'sms_monthly' => $limits['sms_monthly'] ? min(1, $usage['sms_monthly'] / $limits['sms_monthly']) : 0,
            'donations_total' => $limits['donations_total'] ? min(1, $usage['donations_total'] / $limits['donations_total']) : 0,
        ];
    }

    public static function canSendSms(Tenant $tenant, int $additional = 1): bool
    {
        $limit = self::limits($tenant)['sms_monthly'];
        if ($limit === null) return true;
        return self::usage($tenant)['sms_monthly'] + $additional <= $limit;
    }

    public static function canRecordDonation(Tenant $tenant, int $additional = 1): bool
    {
        $limit = self::limits($tenant)['donations_total'];
        if ($limit === null) return true;
        return self::usage($tenant)['donations_total'] + $additional <= $limit;
    }
}
