<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('title');
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('stage_id')->constrained('deal_stages')->restrictOnDelete();
            $table->decimal('value', 15, 2)->default(0);
            $table->char('currency', 3)->default('TRY')->index();
            $table->unsignedTinyInteger('probability')->default(0);
            $table->date('expected_close_date')->nullable()->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->string('status')->default('open')->index();
            $table->string('lost_reason')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('position')->default(0)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'stage_id', 'position']);
            $table->index(['organization_id', 'owner_id']);
            $table->index(['organization_id', 'expected_close_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
