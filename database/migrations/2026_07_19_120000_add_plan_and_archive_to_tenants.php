<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('plan', 30)->default('free')->after('is_active');
            $table->unsignedInteger('sms_limit_monthly')->nullable()->after('plan');
            $table->unsignedInteger('donation_limit_total')->nullable()->after('sms_limit_monthly');
            $table->date('archive_at')->nullable()->after('donation_limit_total');
            $table->timestamp('archived_at')->nullable()->after('archive_at');
            $table->index('plan');
            $table->index('archive_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['plan']);
            $table->dropIndex(['archive_at']);
            $table->dropColumn(['plan', 'sms_limit_monthly', 'donation_limit_total', 'archive_at', 'archived_at']);
        });
    }
};
