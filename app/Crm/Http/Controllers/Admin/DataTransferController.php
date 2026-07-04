<?php

namespace App\Crm\Http\Controllers\Admin;

use App\Crm\Http\Requests\DataTransfer\ImportCrmRecordsRequest;
use App\Crm\Models\CrmImport;
use App\Crm\Services\DataTransfer\CrmDataTransferService;
use App\Crm\Support\CrmLabelCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataTransferController extends Controller
{
    public function __construct(private readonly CrmLabelCatalog $labels) {}

    public function importForm(string $module, CrmDataTransferService $transfer): View
    {
        Gate::authorize("crm.{$module}.import");

        return view('crm::admin.data-transfer.import', [
            'module' => $module,
            'headers' => $transfer->templateHeaders($module),
        ]);
    }

    public function preview(ImportCrmRecordsRequest $request, string $module, CrmDataTransferService $transfer): Response|RedirectResponse
    {
        Gate::authorize("crm.{$module}.import");

        $preview = $transfer->preview($module, $request->file('file'));

        if ($request->ajax()) {
            return response(view('crm::admin.data-transfer._preview', ['preview' => $preview])->render());
        }

        return redirect()
            ->route("crm.{$module}.import")
            ->with('crm_import_preview', $preview)
            ->with('crm_status', trans('crm::messages.import.preview_generated'));
    }

    public function import(ImportCrmRecordsRequest $request, string $module, CrmDataTransferService $transfer): RedirectResponse
    {
        Gate::authorize("crm.{$module}.import");

        $result = $transfer->import($module, $request->file('file'), $request->user());
        $moduleLabel = Str::lower($this->labels->moduleLabel($module));

        return redirect()
            ->route("crm.{$module}.import")
            ->with('crm_import_result', $result)
            ->with('crm_status', $result['queued']
                ? trans('crm::messages.import.queued', ['module' => $moduleLabel])
                : trans('crm::messages.import.completed', [
                    'created' => $result['created'],
                    'module' => $moduleLabel,
                    'failed' => $result['failed'],
                ]));
    }

    public function template(string $module, CrmDataTransferService $transfer): StreamedResponse
    {
        Gate::authorize("crm.{$module}.import");

        return $transfer->streamTemplate($module);
    }

    public function export(Request $request, string $module, CrmDataTransferService $transfer): StreamedResponse
    {
        Gate::authorize("crm.{$module}.export");

        $request->validate([
            'format' => 'nullable|in:csv,excel',
            'columns' => 'nullable|array',
            'columns.*' => 'string',
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
        ]);

        return $transfer->streamExport($module, $request, $request->user());
    }

    public function errors(CrmImport $import, CrmDataTransferService $transfer): StreamedResponse
    {
        Gate::authorize("crm.{$import->module}.import");

        return $transfer->downloadErrorReport($import);
    }
}
