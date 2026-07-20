<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('description', 300)->nullable();
            $table->string('provider_id')->nullable(); // TextTango contact_group UUID
            $table->unsignedInteger('contact_count')->default(0); // denormalized for speed
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'name']);
            $table->index('provider_id');
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 30);
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('notes')->nullable();
            $table->string('provider_id')->nullable(); // TextTango contact UUID
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'phone']);
            $table->index('provider_id');
        });

        Schema::create('contact_group_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['contact_group_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_group_contact');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('contact_groups');
    }
};
