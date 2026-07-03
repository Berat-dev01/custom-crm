<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('name', 120);
            $table->string('url', 500);
            $table->string('secret', 100);
            $table->json('events');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('webhook_id')->constrained('crm_webhooks')->cascadeOnDelete();
            $table->string('event', 80)->index();
            $table->json('payload');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_webhook_deliveries');
        Schema::dropIfExists('crm_webhooks');
    }
};
