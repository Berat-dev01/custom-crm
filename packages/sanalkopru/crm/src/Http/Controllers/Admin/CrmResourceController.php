<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

abstract class CrmResourceController extends Controller
{
    protected string $module;

    protected string $title;

    protected string $permissionPrefix;

    public function index(): View
    {
        $this->authorizeAction('view');

        return $this->view('index');
    }

    public function create(): View
    {
        $this->authorizeAction('create');

        return $this->view('create');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAction('create');

        return $this->notImplemented('store');
    }

    public function show(string $record): View
    {
        $this->authorizeAction('view');

        return $this->view('show', $record);
    }

    public function edit(string $record): View
    {
        $this->authorizeAction('update');

        return $this->view('edit', $record);
    }

    public function update(Request $request, string $record): JsonResponse
    {
        $this->authorizeAction('update');

        return $this->notImplemented('update');
    }

    public function destroy(string $record): RedirectResponse
    {
        $this->authorizeAction('delete');

        abort(Response::HTTP_NOT_IMPLEMENTED, trans('crm::messages.resources.delete_not_implemented', ['title' => $this->title]));
    }

    protected function authorizeAction(string $action): void
    {
        Gate::authorize("{$this->permissionPrefix}.{$action}");
    }

    protected function view(string $screen, ?string $record = null): View
    {
        return view('crm::admin.modules.screen', [
            'module' => $this->module,
            'title' => $this->title,
            'screen' => $screen,
            'record' => $record,
        ]);
    }

    protected function notImplemented(string $action): JsonResponse
    {
        return response()->json([
            'message' => trans('crm::messages.resources.action_registered', ['title' => $this->title, 'action' => $action]),
        ], Response::HTTP_NOT_IMPLEMENTED);
    }
}
