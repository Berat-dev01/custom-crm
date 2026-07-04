<?php

namespace App\Crm\Http\Controllers\Admin;

use App\Crm\Actions\DealStages\DeleteDealStage;
use App\Crm\Actions\DealStages\ReorderDealStages;
use App\Crm\Actions\DealStages\UpsertDealStage;
use App\Crm\Http\Requests\DealStages\DeleteDealStageRequest;
use App\Crm\Http\Requests\DealStages\ReorderDealStagesRequest;
use App\Crm\Http\Requests\DealStages\StoreDealStageRequest;
use App\Crm\Http\Requests\DealStages\UpdateDealStageRequest;
use App\Crm\Models\DealStage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class DealStagesController extends Controller
{
    public function index(): View
    {
        Gate::authorize('crm.settings.manage');

        return view('crm::admin.deal-stages.index', [
            'stages' => DealStage::query()->withCount('deals')->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('crm.settings.manage');

        return view('crm::admin.deal-stages.form', [
            'stage' => new DealStage,
        ]);
    }

    public function store(StoreDealStageRequest $request, UpsertDealStage $upsert): RedirectResponse
    {
        $upsert->handle(new DealStage, $request->payload(), $request->user());

        return redirect()
            ->route('crm.deal-stages.index')
            ->with('crm_status', trans('crm::messages.deal_stages.created'));
    }

    public function edit(DealStage $dealStage): View
    {
        Gate::authorize('crm.settings.manage');

        return view('crm::admin.deal-stages.form', [
            'stage' => $dealStage,
        ]);
    }

    public function update(
        UpdateDealStageRequest $request,
        DealStage $dealStage,
        UpsertDealStage $upsert
    ): RedirectResponse {
        $upsert->handle($dealStage, $request->payload(), $request->user());

        return redirect()
            ->route('crm.deal-stages.index')
            ->with('crm_status', trans('crm::messages.deal_stages.updated'));
    }

    public function destroy(
        DeleteDealStageRequest $request,
        DealStage $dealStage,
        DeleteDealStage $delete
    ): RedirectResponse {
        $delete->handle($dealStage, $request->validated('replacement_stage_id'), $request->user());

        return redirect()
            ->route('crm.deal-stages.index')
            ->with('crm_status', trans('crm::messages.deal_stages.deleted'));
    }

    public function reorder(ReorderDealStagesRequest $request, ReorderDealStages $reorder): RedirectResponse
    {
        $reorder->handle($request->validated('stages'), $request->user());

        return redirect()
            ->route('crm.deal-stages.index')
            ->with('crm_status', trans('crm::messages.deal_stages.reordered'));
    }
}
