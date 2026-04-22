<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->nullableMorphs('taskable');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('reminder_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->string('priority')->default('normal')->index();
            $table->string('status')->default('open')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'assigned_to']);
            $table->index(['organization_id', 'due_at']);
            $table->index(['organization_id', 'reminder_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
