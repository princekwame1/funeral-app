<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('family_name', 200)->nullable()->after('tagline');
            $table->string('deceased_name', 200)->nullable()->after('family_name');
            $table->date('deceased_date_of_birth')->nullable()->after('deceased_name');
            $table->date('deceased_date_of_passing')->nullable()->after('deceased_date_of_birth');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['family_name', 'deceased_name', 'deceased_date_of_birth', 'deceased_date_of_passing']);
        });
    }
};
