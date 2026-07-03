<?php

namespace App\Crm\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use App\Crm\Models\CrmAuditLog;

class AuditLogsController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('crm.settings.manage');

        $filters = $request->validate([
            'event' => ['nullable', 'string', 'max:120'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $logs = CrmAuditLog::query()
            ->with('user:id,name')
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', 'like', '%'.$event.'%'))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['date_from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('crm::admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
        ]);
    }
}
