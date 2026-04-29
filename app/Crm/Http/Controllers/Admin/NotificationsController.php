<?php

namespace App\Crm\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use App\Crm\Services\Notifications\NotificationCenter;

class NotificationsController extends Controller
{
    public function __construct(private readonly NotificationCenter $notifications) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('crm.dashboard.view');

        return response()->json($this->notifications->payload($request->user('admin')));
    }

    public function page(Request $request): View
    {
        Gate::authorize('crm.dashboard.view');

        return view('crm::admin.notifications.index', [
            'notifications' => $this->notifications->paginate($request->user('admin')),
            'unreadCount' => $request->user('admin')?->unreadNotifications()->count() ?? 0,
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        Gate::authorize('crm.dashboard.view');

        $this->notifications->markRead($request->user('admin'), $notification);

        if (! $request->expectsJson()) {
            return back()->with('crm_status', trans('crm::messages.notifications.marked_read'));
        }

        return response()->json($this->notifications->payload($request->user('admin')));
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('crm.dashboard.view');

        $this->notifications->markAllRead($request->user('admin'));

        if (! $request->expectsJson()) {
            return back()->with('crm_status', trans('crm::messages.notifications.marked_all_read'));
        }

        return response()->json($this->notifications->payload($request->user('admin')));
    }
}
