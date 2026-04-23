<?php

namespace Sanalkopru\Crm\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Actions\Contacts\UpsertContact;
use Sanalkopru\Crm\Http\Requests\Contacts\StoreContactRequest;
use Sanalkopru\Crm\Http\Requests\Contacts\UpdateContactRequest;
use Sanalkopru\Crm\Http\Resources\Api\ContactResource;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Services\Contacts\ContactQuery;

class ContactsController extends Controller
{
    public function __construct(private readonly ContactQuery $contacts) {}

    public function index(Request $request): mixed
    {
        Gate::authorize('viewAny', Contact::class);
        $this->validateIndex($request);

        return ContactResource::collection($this->contacts->paginate($request));
    }

    public function store(StoreContactRequest $request, UpsertContact $upsert): mixed
    {
        $contact = $upsert->handle(new Contact, $request->payload(), $request->user());

        return (new ContactResource($contact->load(['company', 'owner', 'tags'])))
            ->additional(['message' => 'Contact created.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Contact $contact): ContactResource
    {
        Gate::authorize('view', $contact);

        return new ContactResource($contact->load(['company', 'owner', 'tags'])->loadCount(['deals', 'tasks', 'quotes']));
    }

    public function update(UpdateContactRequest $request, Contact $contact, UpsertContact $upsert): ContactResource
    {
        $contact = $upsert->handle($contact, $request->payload(), $request->user());

        return (new ContactResource($contact->load(['company', 'owner', 'tags'])))
            ->additional(['message' => 'Contact updated.']);
    }

    private function validateIndex(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'lifecycle_stage' => ['nullable', 'string', 'max:40'],
            'source' => ['nullable', 'string', 'max:80'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'sort' => ['nullable', 'string', 'in:'.implode(',', ContactQuery::SORTS)],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('crm.api.max_per_page', 100)],
        ]);
    }
}
