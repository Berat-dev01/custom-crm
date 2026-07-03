<?php

namespace App\Crm\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use App\Crm\Models\CrmApiToken;
use App\Crm\Services\Audit\CrmAuditLogger;

class ApiTokensController extends Controller
{
    public function __construct(private readonly CrmAuditLogger $audit) {}

    public function index(): View
    {
        Gate::authorize('crm.settings.manage');

        return view('crm::admin.api-tokens.index', [
            'tokens' => CrmApiToken::query()
                ->with('user:id,name,email')
                ->orderByDesc('created_at')
                ->paginate(25),
            'users' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(250)
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('crm.settings.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        $issued = CrmApiToken::issueFor(
            $user,
            $validated['name'],
            ['*'],
            $validated['expires_at'] ?? null
        );

        $this->audit->record('crm.api_token.created', $issued['token'], $request->user(), [], [
            'name' => $validated['name'],
            'user_id' => $user->id,
        ]);

        return redirect()
            ->route('crm.api-tokens.index')
            ->with('crm_status', trans('crm::messages.api_tokens.created'))
            ->with('crm_api_token_plain', $issued['plain_text_token']);
    }

    public function destroy(Request $request, CrmApiToken $apiToken): RedirectResponse
    {
        Gate::authorize('crm.settings.manage');

        $apiToken->delete();

        $this->audit->record('crm.api_token.revoked', $apiToken, $request->user(), [
            'name' => $apiToken->name,
            'user_id' => $apiToken->user_id,
        ], []);

        return redirect()
            ->route('crm.api-tokens.index')
            ->with('crm_status', trans('crm::messages.api_tokens.revoked'));
    }
}
