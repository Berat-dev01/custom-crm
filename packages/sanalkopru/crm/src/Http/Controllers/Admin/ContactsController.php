<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Actions\Contacts\AddContactNote;
use Sanalkopru\Crm\Actions\Contacts\UpsertContact;
use Sanalkopru\Crm\Http\Requests\Contacts\BulkAssignContactTagsRequest;
use Sanalkopru\Crm\Http\Requests\Contacts\BulkDeleteContactsRequest;
use Sanalkopru\Crm\Http\Requests\Contacts\ImportContactsRequest;
use Sanalkopru\Crm\Http\Requests\Contacts\StoreContactNoteRequest;
use Sanalkopru\Crm\Http\Requests\Contacts\StoreContactRequest;
use Sanalkopru\Crm\Http\Requests\Contacts\UpdateContactRequest;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\SavedFilter;
use Sanalkopru\Crm\Models\Tag;
use Sanalkopru\Crm\Services\Audit\CrmAuditLogger;
use Sanalkopru\Crm\Services\Contacts\ContactCsvExporter;
use Sanalkopru\Crm\Services\Contacts\ContactImportService;
use Sanalkopru\Crm\Services\Contacts\ContactQuery;
use Sanalkopru\Crm\Support\CrmExportSchema;
use Sanalkopru\Crm\Support\CrmLabelCatalog;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactsController extends Controller
{
    public function __construct(
        private readonly ContactQuery $contacts,
        private readonly CrmAuditLogger $audit,
        private readonly CrmLabelCatalog $labels
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.contacts.view');

        return view('crm::admin.contacts.index', [
            'contacts' => $this->contacts->paginate($request),
            'filters' => $this->contacts->filters($request),
            'companies' => Company::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'owners' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'color']),
            'savedFilters' => SavedFilter::query()->forModule('contacts')->visibleTo($request->user())->orderBy('name')->get(),
            'lifecycleStages' => $this->labels->lifecycleStages(),
            'exportColumns' => CrmExportSchema::columns('contacts'),
            'exportFormats' => CrmExportSchema::formats('contacts'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('crm.contacts.create');

        return view('crm::admin.contacts.form', $this->formData(new Contact));
    }

    public function store(StoreContactRequest $request, UpsertContact $upsert): RedirectResponse
    {
        $contact = $upsert->handle(new Contact, $request->payload(), $request->user());

        return redirect()
            ->route('crm.contacts.show', $contact)
            ->with('crm_status', 'Contact created.');
    }

    public function show(Contact $contact): View
    {
        Gate::authorize('view', $contact);

        $contact->load([
            'company',
            'owner',
            'tags',
            'deals.stage',
            'tasks.assignee',
            'quotes',
            'activities.user',
        ]);

        return view('crm::admin.contacts.show', [
            'contact' => $contact,
            'openDealsValue' => $contact->deals->where('status', 'open')->sum('value'),
            'openTasks' => $contact->tasks->whereNull('completed_at')->sortBy('due_at'),
            'timeline' => $contact->activities->sortByDesc('occurred_at'),
        ]);
    }

    public function edit(Contact $contact): View
    {
        Gate::authorize('update', $contact);

        return view('crm::admin.contacts.form', $this->formData($contact));
    }

    public function update(UpdateContactRequest $request, Contact $contact, UpsertContact $upsert): RedirectResponse
    {
        $upsert->handle($contact, $request->payload(), $request->user());

        return redirect()
            ->route('crm.contacts.show', $contact)
            ->with('crm_status', 'Contact updated.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        Gate::authorize('delete', $contact);

        $this->audit->record('crm.contact.deleted', $contact, request()->user(), $contact->only($this->auditedFields()), null);
        $contact->delete();

        return redirect()
            ->route('crm.contacts.index')
            ->with('crm_status', 'Contact deleted.');
    }

    public function bulkDelete(BulkDeleteContactsRequest $request): RedirectResponse
    {
        Contact::query()
            ->whereKey($request->validated('contact_ids'))
            ->get()
            ->each(function (Contact $contact) use ($request): void {
                $this->audit->record('crm.contact.deleted', $contact, $request->user(), $contact->only($this->auditedFields()), null, [
                    'bulk' => true,
                ]);
                $contact->delete();
            });

        return back()->with('crm_status', 'Selected contacts deleted.');
    }

    public function bulkTags(BulkAssignContactTagsRequest $request): RedirectResponse
    {
        Contact::query()
            ->whereKey($request->validated('contact_ids'))
            ->get()
            ->each(fn (Contact $contact) => $contact->tags()->syncWithoutDetaching($request->validated('tag_ids')));

        return back()->with('crm_status', 'Tags assigned to selected contacts.');
    }

    public function export(Request $request, ContactCsvExporter $exporter): StreamedResponse
    {
        Gate::authorize('export', Contact::class);

        return $exporter->stream($this->contacts->forExport($request));
    }

    public function importForm(): View
    {
        Gate::authorize('import', Contact::class);

        return view('crm::admin.contacts.import');
    }

    public function import(ImportContactsRequest $request, ContactImportService $importer): RedirectResponse
    {
        $result = $importer->import($request->file('file'), $request->user());

        return redirect()
            ->route('crm.contacts.import')
            ->with('crm_import_result', $result)
            ->with('crm_status', "{$result['created']} contacts imported, {$result['failed']} rows failed.");
    }

    public function storeNote(StoreContactNoteRequest $request, Contact $contact, AddContactNote $addNote): RedirectResponse
    {
        $addNote->handle($contact, $request->validated('body'), $request->user());

        return redirect()
            ->route('crm.contacts.show', $contact)
            ->with('crm_status', 'Note added.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Contact $contact): array
    {
        return [
            'contact' => $contact,
            'companies' => Company::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'owners' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'color']),
            'selectedTags' => $contact->exists ? $contact->tags()->pluck('tags.id')->all() : [],
            'lifecycleStages' => $this->labels->lifecycleStages(),
            'sources' => $this->labels->contactSources(),
        ];
    }

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return [
            'first_name',
            'last_name',
            'full_name',
            'email',
            'phone',
            'company_id',
            'lifecycle_stage',
            'source',
            'owner_id',
        ];
    }
}
