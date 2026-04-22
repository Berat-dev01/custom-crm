<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Http\Requests\DataTransfer\ImportCrmRecordsRequest;
use Sanalkopru\Crm\Models\CrmImport;
use Sanalkopru\Crm\Services\DataTransfer\CrmDataTransferService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataTransferController extends Controller
{
    public function importForm(string $module, CrmDataTransferService $transfer): View
    {
        Gate::authorize("crm.{$module}.import");

        return view('crm::admin.data-transfer.import', [
            'module' => $module,
            'headers' => $transfer->templateHeaders($module),
        ]);
    }

    public function preview(ImportCrmRecordsRequest $request, string $module, CrmDataTransferService $transfer): RedirectResponse
    {
        Gate::authorize("crm.{$module}.import");

        return redirect()
            ->route("crm.{$module}.import")
            ->with('crm_import_preview', $transfer->preview($module, $request->file('file')))
            ->with('crm_status', 'Import preview generated. Upload the file again when you are ready to run the import.');
    }

    public function import(ImportCrmRecordsRequest $request, string $module, CrmDataTransferService $transfer): RedirectResponse
    {
        Gate::authorize("crm.{$module}.import");

        $result = $transfer->import($module, $request->file('file'), $request->user());

        return redirect()
            ->route("crm.{$module}.import")
            ->with('crm_import_result', $result)
            ->with('crm_status', $result['queued']
                ? ucfirst($module).' import queued for background processing.'
                : "{$result['created']} {$module} imported, {$result['failed']} rows failed.");
    }

    public function template(string $module, CrmDataTransferService $transfer): StreamedResponse
    {
        Gate::authorize("crm.{$module}.import");

        return $transfer->streamTemplate($module);
    }

    public function export(Request $request, string $module, CrmDataTransferService $transfer): StreamedResponse
    {
        Gate::authorize("crm.{$module}.export");

        return $transfer->streamExport($module, $request, $request->user());
    }

    public function errors(CrmImport $import, CrmDataTransferService $transfer): StreamedResponse
    {
        Gate::authorize("crm.{$import->module}.import");

        return $transfer->downloadErrorReport($import);
    }
}
