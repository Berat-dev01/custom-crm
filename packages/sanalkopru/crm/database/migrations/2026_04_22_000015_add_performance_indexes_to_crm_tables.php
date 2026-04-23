<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->index(['owner_id', 'lifecycle_stage', 'created_at'], 'contacts_owner_stage_created_idx');
            $table->index(['company_id', 'deleted_at'], 'contacts_company_deleted_idx');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->index(['owner_id', 'sector', 'created_at'], 'companies_owner_sector_created_idx');
        });

        Schema::table('deals', function (Blueprint $table): void {
            $table->index(['stage_id', 'status', 'position', 'id'], 'deals_stage_status_position_idx');
            $table->index(['owner_id', 'status', 'expected_close_date'], 'deals_owner_status_expected_idx');
            $table->index(['status', 'closed_at'], 'deals_status_closed_idx');
            $table->index(['status', 'value'], 'deals_status_value_idx');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->index(['assigned_to', 'status', 'due_at'], 'tasks_assigned_status_due_idx');
            $table->index(['status', 'reminder_at', 'reminder_notified_at'], 'tasks_status_reminder_notified_idx');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->index(['owner_id', 'status', 'created_at'], 'quotes_owner_status_created_idx');
            $table->index(['status', 'valid_until'], 'quotes_status_valid_idx');
            $table->index(['company_id', 'status'], 'quotes_company_status_idx');
        });

        Schema::table('activities', function (Blueprint $table): void {
            $table->index(['user_id', 'occurred_at'], 'activities_user_occurred_idx');
            $table->index(['activityable_type', 'activityable_id', 'occurred_at'], 'activities_subject_occurred_idx');
        });

        Schema::table('tag_relations', function (Blueprint $table): void {
            $table->index(['taggable_type', 'taggable_id', 'tag_id'], 'tag_relations_taggable_tag_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tag_relations', function (Blueprint $table): void {
            $table->dropIndex('tag_relations_taggable_tag_idx');
        });

        Schema::table('activities', function (Blueprint $table): void {
            $table->dropIndex('activities_subject_occurred_idx');
            $table->dropIndex('activities_user_occurred_idx');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropIndex('quotes_company_status_idx');
            $table->dropIndex('quotes_status_valid_idx');
            $table->dropIndex('quotes_owner_status_created_idx');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_status_reminder_notified_idx');
            $table->dropIndex('tasks_assigned_status_due_idx');
        });

        Schema::table('deals', function (Blueprint $table): void {
            $table->dropIndex('deals_status_value_idx');
            $table->dropIndex('deals_status_closed_idx');
            $table->dropIndex('deals_owner_status_expected_idx');
            $table->dropIndex('deals_stage_status_position_idx');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex('companies_owner_sector_created_idx');
        });

        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropIndex('contacts_company_deleted_idx');
            $table->dropIndex('contacts_owner_stage_created_idx');
        });
    }
};
