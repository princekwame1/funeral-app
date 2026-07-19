<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('tagline')->nullable();

            $table->string('brand_primary', 20)->default('#D32F2F');
            $table->string('brand_accent', 20)->default('#9A0007');
            $table->string('logo_url')->nullable();

            $table->string('sms_sender_id', 20)->nullable();
            $table->string('paystack_secret')->nullable();
            $table->string('paystack_public')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
