<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\SmsCampaign;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminSmsController extends Controller
{
    private const SCOPES = ['all', 'paid', 'pending', 'custom'];

    private const KINDS = [
        'notifications' => [
            'heading' => 'SMS Notification',
            'lead' => 'General updates, thank-you and status messages.',
            'templates' => [
                'thanks' => [
                    'label' => 'Thank donors',
                    'body' => 'Thank you for your kind contribution to the family. Your support during this difficult time is deeply appreciated. May God bless you.',
                ],
                'update' => [
                    'label' => 'Funeral update',
                    'body' => 'Dear family and friends, please be informed that the funeral service will take place on [DATE] at [VENUE], starting at [TIME]. Your presence will be appreciated.',
                ],
                'change' => [
                    'label' => 'Schedule change',
                    'body' => 'Please note that the funeral programme originally scheduled for [OLD_DATE] has been moved to [NEW_DATE] at [VENUE]. We appreciate your understanding.',
                ],
            ],
        ],
        'invitations' => [
            'heading' => 'Funeral Invitation',
            'lead' => 'Invite family, friends and community to the funeral service.',
            'templates' => [
                'invite' => [
                    'label' => 'Invitation',
                    'body' => 'You are cordially invited to the funeral service of our beloved [NAME]. Date: [DATE]. Venue: [VENUE]. Time: [TIME]. We look forward to your presence.',
                ],
                'reminder' => [
                    'label' => 'Reminder',
                    'body' => 'Reminder: The funeral service is scheduled for tomorrow at [VENUE], [TIME]. We look forward to your presence. Thank you.',
                ],
                'directions' => [
                    'label' => 'Directions / venue',
                    'body' => 'Directions to the funeral service of the late [NAME]: [VENUE], [LANDMARK]. Programme begins at [TIME] on [DATE]. Safe travels.',
                ],
            ],
        ],
        'post' => [
            'heading' => 'Post Notification',
            'lead' => 'Post-funeral thanks, appreciation and follow-up messages.',
            'templates' => [
                'appreciation' => [
                    'label' => 'Post-funeral thanks',
                    'body' => 'On behalf of the family, we sincerely thank you for your presence, prayers, and contributions during our recent bereavement. May God richly bless you.',
                ],
                'contribution' => [
                    'label' => 'Contribution acknowledgement',
                    'body' => 'The family of the late [NAME] is deeply grateful for your generous contribution. May the Almighty replenish all you have given.',
                ],
                'memorial' => [
                    'label' => 'Memorial announcement',
                    'body' => 'The family will hold a memorial service in honour of the late [NAME] on [DATE] at [VENUE], [TIME]. We invite you to join us as we remember them.',
                ],
            ],
        ],
    ];

    public function show(string $kind = 'notifications')
    {
        abort_unless(isset(self::KINDS[$kind]), 404);
        $config = self::KINDS[$kind];

        $counts = [
            'all' => $this->recipientsFor('all')->count(),
            'paid' => $this->recipientsFor('paid')->count(),
            'pending' => $this->recipientsFor('pending')->count(),
        ];

        $campaigns = SmsCampaign::query()
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        $campaignTotals = [
            'total_sent' => (int) SmsCampaign::sum('sent_count'),
            'total_recipients' => (int) SmsCampaign::sum('recipient_count'),
            'thank_you_sent' => Donation::where('sms_sent', true)->count(),
        ];

        $templates = $config['templates'];
        $heading = $config['heading'];
        $lead = $config['lead'];

        return view('admin.sms.compose', compact(
            'counts', 'templates', 'campaigns', 'campaignTotals', 'heading', 'lead', 'kind'
        ));
    }

    public function send(Request $request, SmsService $sms)
    {
        $data = $request->validate([
            'scope' => 'required|in:' . implode(',', self::SCOPES),
            'message' => 'required|string|min:1|max:1071',
            'campaign_name' => 'nullable|string|max:255',
            'custom_phones' => 'nullable|string|max:20000',
            'kind' => 'nullable|in:notifications,invitations,post',
        ]);

        $kind = $data['kind'] ?? 'notifications';
        $sendPermMap = [
            'notifications' => \App\Support\Permissions::SMS_NOTIFICATIONS_SEND,
            'invitations'   => \App\Support\Permissions::SMS_INVITATIONS_SEND,
            'post'          => \App\Support\Permissions::SMS_POST_SEND,
        ];
        abort_unless($request->user()->can($sendPermMap[$kind]), 403, 'You cannot send from this SMS section.');

        $recipients = $data['scope'] === 'custom'
            ? $this->parseCustomPhones($data['custom_phones'] ?? '')
            : $this->recipientsFor($data['scope']);

        if ($recipients->isEmpty()) {
            return back()
                ->withInput()
                ->with('sms_error', 'No recipients matched the selected scope.');
        }

        $tenant = app(\App\Support\CurrentTenant::class)->get();
        if ($tenant && ! \App\Support\Plans::canSendSms($tenant, $recipients->count())) {
            $limit = \App\Support\Plans::limits($tenant)['sms_monthly'];
            $used = \App\Support\Plans::usage($tenant)['sms_monthly'];
            $remaining = max(0, $limit - $used);
            return back()->withInput()->with('sms_error', "Plan limit reached: {$limit} SMS per month (used {$used}, only {$remaining} left). Upgrade the tenant's plan.");
        }

        $result = $sms->sendBulk(
            $recipients,
            $data['message'],
            $data['campaign_name'] ?? null,
        );

        $campaign = SmsCampaign::create([
            'user_id' => $request->user()->id,
            'campaign_name' => $data['campaign_name'] ?? null,
            'scope' => $data['scope'],
            'message' => $data['message'],
            'sender_id' => (string) config('services.sms.sender_id', 'Funeral'),
            'recipient_count' => (int) $result['total'],
            'sent_count' => (int) $result['sent'],
            'failed_count' => (int) $result['failed'],
            'skipped_count' => (int) $result['skipped'],
            'provider_campaign_id' => $result['campaign_id'] ?? null,
            'status' => $this->deriveCampaignStatus($result),
        ]);

        $result['campaign_row_id'] = $campaign->id;

        return redirect()
            ->route('admin.sms.show')
            ->with('sms_result', $result);
    }

    private function deriveCampaignStatus(array $result): string
    {
        if (($result['total'] ?? 0) === 0) return SmsCampaign::STATUS_EMPTY;
        if (($result['failed'] ?? 0) === 0) return SmsCampaign::STATUS_SENT;
        if (($result['sent'] ?? 0) === 0) return SmsCampaign::STATUS_FAILED;
        return SmsCampaign::STATUS_PARTIAL;
    }

    public function logs(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('q');

        $query = SmsCampaign::query()->with('user')->latest();

        if ($status && in_array($status, ['sent', 'partial', 'failed', 'empty'], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('campaign_name', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('provider_campaign_id', 'like', "%{$search}%");
            });
        }

        $campaigns = $query->paginate(25)->withQueryString();

        $totals = [
            'campaigns' => SmsCampaign::count(),
            'total_sent' => (int) SmsCampaign::sum('sent_count'),
            'total_failed' => (int) SmsCampaign::sum('failed_count'),
            'thank_you_sent' => Donation::where('sms_sent', true)->count(),
        ];

        return view('admin.sms.logs', compact('campaigns', 'totals', 'status', 'search'));
    }

    private function recipientsFor(string $scope): Collection
    {
        $query = Donation::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        if ($scope === 'paid') {
            $query->where('status', Donation::STATUS_PAID);
        } elseif ($scope === 'pending') {
            $query->where('status', Donation::STATUS_PENDING);
        }

        return $query->distinct()->pluck('phone');
    }

    private function parseCustomPhones(string $input): Collection
    {
        return collect(preg_split('/[\s,;]+/', $input, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($p) => trim($p))
            ->filter()
            ->unique()
            ->values();
    }
}
