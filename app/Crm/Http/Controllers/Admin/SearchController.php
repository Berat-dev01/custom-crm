<?php

namespace App\Crm\Http\Controllers\Admin;

use App\Crm\Services\Search\CrmGlobalSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

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
