<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $map = [
        'Sanalkopru\Crm\Models\Contact' => 'contact',
        'Sanalkopru\Crm\Models\Company' => 'company',
        'Sanalkopru\Crm\Models\Deal' => 'deal',
        'Sanalkopru\Crm\Models\Quote' => 'quote',
        'Sanalkopru\Crm\Models\Task' => 'task',
        'Sanalkopru\Crm\Models\Activity' => 'activity',
        'Sanalkopru\Crm\Models\Tag' => 'tag',
        'Sanalkopru\Crm\Models\CrmExport' => 'crm_export',
    ];

    public function up(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table('tasks')->where('taskable_type', $old)->update(['taskable_type' => $new]);
            DB::table('activities')->where('activityable_type', $old)->update(['activityable_type' => $new]);
            DB::table('crm_audit_logs')->where('auditable_type', $old)->update(['auditable_type' => $new]);
            DB::table('tag_relations')->where('taggable_type', $old)->update(['taggable_type' => $new]);
        }
    }

    public function down(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table('tasks')->where('taskable_type', $new)->update(['taskable_type' => $old]);
            DB::table('activities')->where('activityable_type', $new)->update(['activityable_type' => $old]);
            DB::table('crm_audit_logs')->where('auditable_type', $new)->update(['auditable_type' => $old]);
            DB::table('tag_relations')->where('taggable_type', $new)->update(['taggable_type' => $old]);
        }
    }
};
