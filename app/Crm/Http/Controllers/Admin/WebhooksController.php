<?php

namespace App\Crm\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Crm\Models\CrmWebhook;
use App\Crm\Models\CrmWebhookDelivery;
use App\Crm\Services\Audit\CrmAuditLogger;

class WebhooksController extends Controller
{
    public function __construct(private readonly CrmAuditLogger $audit) {}

    public function index(): View
    {
        Gate::authorize('crm.settings.manage');

        return view('crm::admin.webhooks.index', [
            'webhooks' => CrmWebhook::query()
                ->withCount('deliveries')
                ->orderByDesc('created_at')
                ->paginate(25),
            'deliveries' => CrmWebhookDelivery::query()
                ->with('webhook:id,name')
                ->orderByDesc('id')
                ->limit(20)
                ->get(),
            'availableEvents' => CrmWebhook::EVENTS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('crm.settings.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url:https,http', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', Rule::in(CrmWebhook::EVENTS)],
        ]);

        $secret = 'whsec_'.Str::random(40);

        $webhook = CrmWebhook::query()->create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'secret' => $secret,
            'events' => array_values($validated['events']),
            'is_active' => true,
            'created_by' => $request->user()?->getAuthIdentifier(),
        ]);

        $this->audit->record('crm.webhook.created', $webhook, $request->user(), [], [
            'name' => $webhook->name,
            'url' => $webhook->url,
            'events' => $webhook->events,
        ]);

        return redirect()
            ->route('crm.webhooks.index')
            ->with('crm_status', trans('crm::messages.webhooks.created'))
            ->with('crm_webhook_secret', $secret);
    }

    public function toggle(Request $request, CrmWebhook $webhook): RedirectResponse
    {
        Gate::authorize('crm.settings.manage');

        $webhook->forceFill(['is_active' => ! $webhook->is_active])->save();

        $this->audit->record('crm.webhook.toggled', $webhook, $request->user(), [], [
            'is_active' => $webhook->is_active,
        ]);

        return redirect()
            ->route('crm.webhooks.index')
            ->with('crm_status', trans($webhook->is_active
                ? 'crm::messages.webhooks.enabled'
                : 'crm::messages.webhooks.disabled'));
    }

    public function destroy(Request $request, CrmWebhook $webhook): RedirectResponse
    {
        Gate::authorize('crm.settings.manage');

        $webhook->delete();

        $this->audit->record('crm.webhook.deleted', $webhook, $request->user(), [
            'name' => $webhook->name,
            'url' => $webhook->url,
        ], []);

        return redirect()
            ->route('crm.webhooks.index')
            ->with('crm_status', trans('crm::messages.webhooks.deleted'));
    }
}
