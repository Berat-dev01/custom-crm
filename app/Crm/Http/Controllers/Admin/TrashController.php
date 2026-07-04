<?php

namespace App\Crm\Http\Controllers\Admin;

use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;
use App\Crm\Services\Audit\CrmAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TrashController extends Controller
{
    /**
     * @var array<string, array{model: class-string, label_field: string}>
     */
    private const MODULES = [
        'contacts' => ['model' => Contact::class, 'label_field' => 'full_name'],
        'companies' => ['model' => Company::class, 'label_field' => 'name'],
        'deals' => ['model' => Deal::class, 'label_field' => 'title'],
        'quotes' => ['model' => Quote::class, 'label_field' => 'quote_number'],
    ];

    public function __construct(private readonly CrmAuditLogger $audit) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.settings.manage');

        $validated = $request->validate([
            'module' => ['nullable', 'string', Rule::in(array_keys(self::MODULES))],
        ]);

        $module = $validated['module'] ?? 'contacts';
        $config = self::MODULES[$module];

        return view('crm::admin.trash.index', [
            'module' => $module,
            'modules' => array_keys(self::MODULES),
            'labelField' => $config['label_field'],
            'records' => $config['model']::onlyTrashed()
                ->orderByDesc('deleted_at')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function restore(Request $request, string $module, int $id): RedirectResponse
    {
        Gate::authorize('crm.settings.manage');
        $config = $this->moduleConfig($module);

        $record = $config['model']::onlyTrashed()->findOrFail($id);
        $record->restore();

        $this->audit->record('crm.trash.restored', $record, $request->user(), [], [
            'module' => $module,
        ]);

        return back()->with('crm_status', trans('crm::messages.trash.restored'));
    }

    public function destroy(Request $request, string $module, int $id): RedirectResponse
    {
        Gate::authorize('crm.settings.manage');
        $config = $this->moduleConfig($module);

        $record = $config['model']::onlyTrashed()->findOrFail($id);

        $this->audit->record('crm.trash.purged', $record, $request->user(), [
            'module' => $module,
            'label' => $record->{$config['label_field']},
        ], []);

        $record->forceDelete();

        return back()->with('crm_status', trans('crm::messages.trash.purged'));
    }

    /**
     * @return array{model: class-string, label_field: string}
     */
    private function moduleConfig(string $module): array
    {
        abort_unless(isset(self::MODULES[$module]), 404);

        return self::MODULES[$module];
    }
}
