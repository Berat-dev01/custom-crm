<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CrmDatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_core_tables_are_created(): void
    {
        foreach ($this->coreTables() as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing CRM table [{$table}].");
        }
    }

    public function test_crm_support_tables_are_created(): void
    {
        foreach ($this->supportTables() as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing CRM support table [{$table}].");
        }
    }

    public function test_core_tables_include_required_columns(): void
    {
        $this->assertColumns('contacts', [
            'public_id',
            'organization_id',
            'first_name',
            'last_name',
            'full_name',
            'email',
            'phone',
            'company_id',
            'owner_id',
            'last_contacted_at',
            'custom_fields',
            'deleted_at',
        ]);

        $this->assertColumns('companies', [
            'public_id',
            'organization_id',
            'name',
            'email',
            'phone',
            'website',
            'tax_number',
            'tax_office',
            'address_line_1',
            'city',
            'sector',
            'owner_id',
            'custom_fields',
            'deleted_at',
        ]);

        $this->assertColumns('deals', [
            'public_id',
            'organization_id',
            'title',
            'contact_id',
            'company_id',
            'stage_id',
            'value',
            'currency',
            'probability',
            'expected_close_date',
            'closed_at',
            'status',
            'lost_reason',
            'owner_id',
            'position',
            'custom_fields',
            'deleted_at',
        ]);

        $this->assertColumns('quotes', [
            'public_id',
            'organization_id',
            'quote_number',
            'contact_id',
            'company_id',
            'deal_id',
            'status',
            'currency',
            'subtotal',
            'discount_type',
            'discount_value',
            'discount_total',
            'tax_rate',
            'tax_total',
            'grand_total',
            'valid_until',
            'sent_at',
            'accepted_at',
            'rejected_at',
            'deleted_at',
        ]);
    }

    public function test_operational_tables_include_required_columns(): void
    {
        $this->assertColumns('tasks', [
            'public_id',
            'organization_id',
            'title',
            'description',
            'taskable_type',
            'taskable_id',
            'assigned_to',
            'due_at',
            'reminder_at',
            'reminder_notified_at',
            'completed_at',
            'priority',
            'status',
            'deleted_at',
        ]);

        $this->assertColumns('activities', [
            'public_id',
            'organization_id',
            'subject',
            'body',
            'type',
            'activityable_type',
            'activityable_id',
            'user_id',
            'occurred_at',
            'metadata',
            'deleted_at',
        ]);

        $this->assertColumns('tag_relations', [
            'organization_id',
            'tag_id',
            'taggable_type',
            'taggable_id',
            'created_by',
        ]);
    }

    public function test_support_tables_include_required_columns(): void
    {
        $this->assertColumns('crm_settings', [
            'organization_id',
            'group',
            'key',
            'value',
            'type',
            'is_encrypted',
        ]);

        $this->assertColumns('crm_imports', [
            'public_id',
            'organization_id',
            'module',
            'filename',
            'status',
            'total_rows',
            'processed_rows',
            'failed_rows',
            'error_report_path',
            'options',
        ]);

        $this->assertColumns('crm_audit_logs', [
            'organization_id',
            'event',
            'auditable_type',
            'auditable_id',
            'user_id',
            'old_values',
            'new_values',
            'metadata',
            'ip_address',
            'user_agent',
        ]);

        $this->assertColumns('crm_saved_filters', [
            'public_id',
            'organization_id',
            'name',
            'module',
            'filters',
            'visibility',
            'is_default',
            'user_id',
            'deleted_at',
        ]);

        $this->assertColumns('crm_api_tokens', [
            'public_id',
            'user_id',
            'name',
            'token_hash',
            'abilities',
            'last_used_at',
            'expires_at',
            'deleted_at',
        ]);

        $this->assertColumns('notifications', [
            'id',
            'type',
            'notifiable_type',
            'notifiable_id',
            'data',
            'read_at',
        ]);
    }

    /**
     * @return list<string>
     */
    private function coreTables(): array
    {
        return [
            'companies',
            'contacts',
            'deal_stages',
            'deals',
            'tasks',
            'quotes',
            'quote_items',
            'activities',
            'tags',
            'tag_relations',
        ];
    }

    /**
     * @return list<string>
     */
    private function supportTables(): array
    {
        return [
            'crm_settings',
            'crm_imports',
            'crm_exports',
            'crm_saved_filters',
            'crm_audit_logs',
            'crm_api_tokens',
        ];
    }

    /**
     * @param  list<string>  $columns
     */
    private function assertColumns(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn($table, $column),
                "Missing column [{$table}.{$column}]."
            );
        }
    }
}
