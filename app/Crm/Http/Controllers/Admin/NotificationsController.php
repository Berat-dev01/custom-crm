<?php

namespace App\Crm\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use App\Crm\Services\Notifications\NotificationCenter;
use App\Crm\Services\Notifications\NotificationPreferences;

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
            'emailEvents' => [
                'task_reminders' => __('Task reminders'),
                'task_assignments' => __('Task assignments'),
                'quote_status_changes' => __('Quote status changes'),
                'deal_closed' => __('Deal won/lost results'),
                'import_status_updates' => __('Import status updates'),
                'weekly_digest' => __('Weekly digest email'),
            ],
            'emailPrefs' => $request->user('admin')?->notification_email_prefs ?? [],
        ]);
    }

    public function preferences(Request $request): RedirectResponse
    {
        Gate::authorize('crm.dashboard.view');

        $events = NotificationPreferences::emailEvents();

        $validated = $request->validate(
            collect($events)->mapWithKeys(fn (string $event): array => [
                "email_prefs.{$event}" => ['nullable', 'boolean'],
            ])->all()
        );

        $prefs = collect($events)->mapWithKeys(fn (string $event): array => [
            $event => (bool) data_get($validated, "email_prefs.{$event}", false),
        ])->all();

        $request->user('admin')->forceFill(['notification_email_prefs' => $prefs])->save();

        return back()->with('crm_status', trans('crm::messages.notifications.preferences_saved'));
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
