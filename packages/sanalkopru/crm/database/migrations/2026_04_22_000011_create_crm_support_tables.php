<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('group')->default('general')->index();
            $table->string('key');
            $table->json('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_encrypted')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'key']);
        });

        Schema::create('crm_imports', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('module')->index();
            $table->string('filename')->nullable();
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('error_report_path')->nullable();
            $table->json('options')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'module']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('crm_exports', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('module')->index();
            $table->string('filename')->nullable();
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->json('filters')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'module']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('crm_saved_filters', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('name');
            $table->string('module')->index();
            $table->json('filters');
            $table->string('visibility')->default('private')->index();
            $table->boolean('is_default')->default(false)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'module']);
            $table->index(['organization_id', 'user_id']);
        });

        Schema::create('crm_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('event')->index();
            $table->nullableMorphs('auditable');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['organization_id', 'event']);
            $table->index(['organization_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_audit_logs');
        Schema::dropIfExists('crm_saved_filters');
        Schema::dropIfExists('crm_exports');
        Schema::dropIfExists('crm_imports');
        Schema::dropIfExists('crm_settings');
    }
};
