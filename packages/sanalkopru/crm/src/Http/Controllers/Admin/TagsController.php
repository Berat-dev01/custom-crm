<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Actions\Tags\BulkTagRecords;
use Sanalkopru\Crm\Actions\Tags\UpsertTag;
use Sanalkopru\Crm\Http\Requests\Tags\BulkTagRecordsRequest;
use Sanalkopru\Crm\Http\Requests\Tags\StoreTagRequest;
use Sanalkopru\Crm\Http\Requests\Tags\UpdateTagRequest;
use Sanalkopru\Crm\Models\Tag;

class TagsController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('crm.tags.view');

        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        return view('crm::admin.tags.index', [
            'tags' => Tag::query()
                ->withCount(['contacts', 'companies', 'deals', 'quotes'])
                ->search($request->string('search')->toString())
                ->orderBy('name')
                ->paginate($this->perPage($request))
                ->withQueryString(),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('crm.tags.create');

        return view('crm::admin.tags.form', [
            'tag' => new Tag,
        ]);
    }

    public function store(StoreTagRequest $request, UpsertTag $upsert): RedirectResponse
    {
        $tag = $upsert->handle(new Tag, $request->validated(), $request->user());

        return redirect()
            ->route('crm.tags.show', $tag)
            ->with('crm_status', 'Tag created.');
    }

    public function show(Tag $tag): View
    {
        Gate::authorize('view', $tag);

        $tag->loadCount(['contacts', 'companies', 'deals', 'quotes']);

        return view('crm::admin.tags.show', [
            'tag' => $tag,
        ]);
    }

    public function edit(Tag $tag): View
    {
        Gate::authorize('update', $tag);

        return view('crm::admin.tags.form', [
            'tag' => $tag,
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $tag, UpsertTag $upsert): RedirectResponse
    {
        $upsert->handle($tag, $request->validated(), $request->user());

        return redirect()
            ->route('crm.tags.show', $tag)
            ->with('crm_status', 'Tag updated.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        Gate::authorize('delete', $tag);

        $tag->delete();

        return redirect()
            ->route('crm.tags.index')
            ->with('crm_status', 'Tag deleted.');
    }

    public function bulk(BulkTagRecordsRequest $request, BulkTagRecords $bulkTagRecords): RedirectResponse
    {
        $count = $bulkTagRecords->handle(
            $request->validated('taggable_type'),
            $request->validated('record_ids') ?? $request->validated('contact_ids'),
            $request->validated('tag_ids'),
            $request->validated('mode'),
            $request->user()
        );

        return back()->with('crm_status', "{$count} record(s) updated.");
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('crm.api.default_per_page', 20);
        $max = (int) config('crm.api.max_per_page', 100);

        return min(max(1, $request->integer('per_page', $default)), $max);
    }
}
