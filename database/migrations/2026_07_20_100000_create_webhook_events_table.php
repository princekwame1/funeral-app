<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->index();
            $table->string('event', 80)->nullable();
            $table->string('reference', 120)->nullable()->index();
            $table->boolean('signature_ok')->default(false);
            $table->unsignedSmallInteger('response_status')->default(200);
            $table->text('payload')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['provider', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
