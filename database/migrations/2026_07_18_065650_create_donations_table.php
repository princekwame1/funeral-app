<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('donor_name');
            $table->string('phone');
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('GHS');
            $table->string('status')->default('pending');
            $table->string('paystack_reference')->nullable()->unique();
            $table->string('paystack_channel')->nullable();
            $table->string('gateway_response')->nullable();
            $table->boolean('sms_sent')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
