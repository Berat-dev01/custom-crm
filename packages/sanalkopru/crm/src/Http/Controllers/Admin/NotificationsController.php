<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Services\Notifications\NotificationCenter;

class NotificationsController extends Controller
{
    public function __construct(private readonly NotificationCenter $notifications) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('crm.dashboard.view');

        return response()->json($this->notifications->payload($request->user('admin')));
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        Gate::authorize('crm.dashboard.view');

        $this->notifications->markRead($request->user('admin'), $notification);

        return response()->json($this->notifications->payload($request->user('admin')));
    }

    public function readAll(Request $request): JsonResponse
    {
        Gate::authorize('crm.dashboard.view');

        $this->notifications->markAllRead($request->user('admin'));

        return response()->json($this->notifications->payload($request->user('admin')));
    }
}
