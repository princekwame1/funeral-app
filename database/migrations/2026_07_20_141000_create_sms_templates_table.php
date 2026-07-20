<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);        // thankyou | notifications | invitations | post
            $table->string('slug', 60);        // key within kind (e.g. thanks, reminder)
            $table->string('label', 150);
            $table->text('body');
            $table->boolean('is_default')->default(false); // used by auto-fire (thankyou)
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'kind', 'slug']);
            $table->index(['tenant_id', 'kind']);
        });

        // Seed per-tenant defaults so existing tenants get the standard preset library.
        $now = now();
        $tenants = DB::table('tenants')->pluck('id');
        foreach ($tenants as $tenantId) {
            $rows = self::defaultTemplateRows($tenantId, $now);
            if ($rows) DB::table('sms_templates')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }

    public static function defaultTemplateRows(int $tenantId, $now): array
    {
        $mk = fn (string $kind, string $slug, string $label, string $body, bool $isDefault, int $order) => [
            'tenant_id' => $tenantId,
            'kind' => $kind,
            'slug' => $slug,
            'label' => $label,
            'body' => $body,
            'is_default' => $isDefault,
            'sort_order' => $order,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        return [
            // --- Auto-fire after a paid donation ---
            $mk('thankyou', 'default', 'Payment thank-you (auto-sent)',
                'Dear [DONOR], thank you for your kind contribution of [AMOUNT] to the family. May God bless you.',
                true, 1),

            // --- General notifications page ---
            $mk('notifications', 'thanks', 'Thank donors',
                'Thank you for your kind contribution to the family. Your support during this difficult time is deeply appreciated. May God bless you.',
                false, 1),
            $mk('notifications', 'update', 'Funeral update',
                'Dear family and friends, please be informed that the funeral service will take place on [DATE] at [VENUE], starting at [TIME]. Your presence will be appreciated.',
                false, 2),
            $mk('notifications', 'change', 'Schedule change',
                'Please note that the funeral programme originally scheduled for [OLD_DATE] has been moved to [NEW_DATE] at [VENUE]. We appreciate your understanding.',
                false, 3),

            // --- Invitations page ---
            $mk('invitations', 'invite', 'Invitation',
                'You are cordially invited to the funeral service of our beloved [DECEASED]. Date: [DATE]. Venue: [VENUE]. Time: [TIME]. We look forward to your presence.',
                false, 1),
            $mk('invitations', 'reminder', 'Reminder',
                'Reminder: The funeral service is scheduled for tomorrow at [VENUE], [TIME]. We look forward to your presence. Thank you.',
                false, 2),
            $mk('invitations', 'directions', 'Directions / venue',
                'Directions to the funeral service of the late [DECEASED]: [VENUE], [LANDMARK]. Programme begins at [TIME] on [DATE]. Safe travels.',
                false, 3),

            // --- Post-funeral page ---
            $mk('post', 'appreciation', 'Post-funeral thanks',
                'On behalf of the family, we sincerely thank you for your presence, prayers, and contributions during our recent bereavement. May God richly bless you.',
                false, 1),
            $mk('post', 'contribution', 'Contribution acknowledgement',
                'The family of the late [DECEASED] is deeply grateful for your generous contribution. May the Almighty replenish all you have given.',
                false, 2),
            $mk('post', 'memorial', 'Memorial announcement',
                'The family will hold a memorial service in honour of the late [DECEASED] on [DATE] at [VENUE], [TIME]. We invite you to join us as we remember them.',
                false, 3),
        ];
    }
};
