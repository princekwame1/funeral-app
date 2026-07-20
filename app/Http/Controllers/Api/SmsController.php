<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactGroup;
use App\Models\SmsCampaign;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use App\Support\CurrentTenant;
use App\Support\Permissions;
use App\Support\Plans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    /**
     * List reusable SMS templates for the current tenant.
     * Optional filter: ?kind=thankyou|notifications|invitations|post
     */
    public function templates(Request $request): JsonResponse
    {
        $q = SmsTemplate::query()->orderBy('kind')->orderBy('sort_order')->orderBy('id');
        if ($kind = $request->query('kind')) {
            $q->where('kind', $kind);
        }
        return response()->json(['templates' => $q->get()]);
    }

    public function send(Request $request, SmsService $sms): JsonResponse
    {
        abort_unless(
            $request->user()?->can(Permissions::SMS_NOTIFICATIONS_SEND),
            403,
            'You do not have permission to send SMS.'
        );

        $data = $request->validate([
            'group_id' => ['required', 'integer', 'exists:contact_groups,id'],
            'message' => ['required', 'string', 'min:1', 'max:1071'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
        ]);

        $group = ContactGroup::findOrFail($data['group_id']);
        $recipients = $group->contacts()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->pluck('phone');

        if ($recipients->isEmpty()) {
            return response()->json([
                'message' => 'This group has no contacts with phone numbers.',
            ], 422);
        }

        $tenant = app(CurrentTenant::class)->get();
        if ($tenant && ! Plans::canSendSms($tenant, $recipients->count())) {
            $limits = Plans::limits($tenant);
            $usage = Plans::usage($tenant);
            $limit = $limits['sms_monthly'] ?? 0;
            $used = $usage['sms_monthly'] ?? 0;
            $remaining = max(0, $limit - $used);
            return response()->json([
                'message' => "Plan limit reached: {$limit} SMS/month (used {$used}, {$remaining} left).",
            ], 429);
        }

        $result = $sms->sendBulk(
            $recipients,
            $data['message'],
            $data['campaign_name'] ?? null,
        );

        $campaign = SmsCampaign::create([
            'user_id' => $request->user()->id,
            'campaign_name' => $data['campaign_name'] ?? null,
            'scope' => 'group',
            'message' => $data['message'],
            'sender_id' => (string) config('services.sms.sender_id', 'Funeral'),
            'recipient_count' => (int) $result['total'],
            'sent_count' => (int) $result['sent'],
            'failed_count' => (int) $result['failed'],
            'skipped_count' => (int) $result['skipped'],
            'provider_campaign_id' => $result['campaign_id'] ?? null,
            'status' => $this->deriveStatus($result),
        ]);

        return response()->json([
            'campaign' => $campaign,
            'result' => [
                'sent' => (int) $result['sent'],
                'failed' => (int) $result['failed'],
                'skipped' => (int) $result['skipped'],
                'total' => (int) $result['total'],
            ],
        ]);
    }

    private function deriveStatus(array $result): string
    {
        if (($result['total'] ?? 0) === 0) return SmsCampaign::STATUS_EMPTY;
        if (($result['failed'] ?? 0) === 0) return SmsCampaign::STATUS_SENT;
        if (($result['sent'] ?? 0) === 0) return SmsCampaign::STATUS_FAILED;
        return SmsCampaign::STATUS_PARTIAL;
    }
}
