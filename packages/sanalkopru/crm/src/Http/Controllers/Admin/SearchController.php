<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Services\Search\CrmGlobalSearch;

class SearchController extends Controller
{
    public function __invoke(Request $request, CrmGlobalSearch $search): View
    {
        Gate::authorize('crm.dashboard.view');

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:120'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));

        return view('crm::admin.search.index', [
            'term' => $term,
            'groups' => $search->search($term, $request->user()),
        ]);
    }
}
