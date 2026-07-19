<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('name', 100);
            $table->string('tagline', 200)->nullable();
            $table->unsignedInteger('sms_monthly')->nullable(); // null = unlimited
            $table->unsignedInteger('donations_total')->nullable();
            $table->unsignedInteger('price_ghs')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed the current in-code plans so tenants keep working.
        $now = now();
        DB::table('plans')->insert([
            ['slug' => 'free', 'name' => 'Free', 'tagline' => 'Perfect for small family gatherings.', 'sms_monthly' => 100, 'donations_total' => 500, 'price_ghs' => 0, 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'starter', 'name' => 'Starter', 'tagline' => 'Room to grow for medium-sized funerals.', 'sms_monthly' => 1000, 'donations_total' => 5000, 'price_ghs' => 100, 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'pro', 'name' => 'Pro', 'tagline' => 'Unlimited SMS and donations. No caps.', 'sms_monthly' => null, 'donations_total' => null, 'price_ghs' => 500, 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
