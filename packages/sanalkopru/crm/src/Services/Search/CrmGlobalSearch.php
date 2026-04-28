<?php

namespace Sanalkopru\Crm\Services\Search;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Support\CrmFormatter;

class CrmGlobalSearch
{
    public function __construct(private readonly CrmFormatter $formatter) {}

    /**
     * @return array<string, array{label: string, permission: string, total: int, items: list<array<string, string>>}>
     */
    public function search(string $term, ?Authenticatable $user = null, int $limit = 5): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return $this->emptyGroups();
        }

        $groups = $this->emptyGroups();

        if ($this->can($user, 'crm.contacts.view')) {
            $contacts = Contact::query()
                ->with('company:id,name')
                ->where(function (Builder $query) use ($term): void {
                    $query->where('full_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('title', 'like', "%{$term}%");
                })
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get();

            $groups['contacts']['items'] = $contacts
                ->map(fn (Contact $contact): array => [
                    'title' => $contact->full_name,
                    'subtitle' => collect([$contact->company?->name, $contact->email, $contact->phone])->filter()->implode(' / ') ?: __('Contact'),
                    'url' => route('crm.contacts.show', $contact),
                    'badge' => $this->formatter->status((string) $contact->lifecycle_stage),
                ])
                ->all();
            $groups['contacts']['total'] = count($groups['contacts']['items']);
        }

        if ($this->can($user, 'crm.companies.view')) {
            $companies = Company::query()
                ->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('tax_number', 'like', "%{$term}%");
                })
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get();

            $groups['companies']['items'] = $companies
                ->map(fn (Company $company): array => [
                    'title' => $company->name,
                    'subtitle' => collect([$company->sector, $company->city, $company->email])->filter()->implode(' / ') ?: __('Company'),
                    'url' => route('crm.companies.show', $company),
                    'badge' => $company->sector ?: __('Company'),
                ])
                ->all();
            $groups['companies']['total'] = count($groups['companies']['items']);
        }

        if ($this->can($user, 'crm.deals.view')) {
            $deals = Deal::query()
                ->with(['company:id,name', 'contact:id,full_name', 'stage:id,name'])
                ->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', "%{$term}%")
                        ->orWhereHas('company', fn (Builder $query) => $query->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('contact', fn (Builder $query) => $query->where('full_name', 'like', "%{$term}%"));
                })
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get();

            $groups['deals']['items'] = $deals
                ->map(fn (Deal $deal): array => [
                    'title' => $deal->title,
                    'subtitle' => collect([
                        $deal->company?->name ?: $deal->contact?->full_name,
                        $deal->stage?->name,
                        $this->formatter->money($deal->value, $deal->currency),
                    ])->filter()->implode(' / '),
                    'url' => route('crm.deals.show', $deal),
                    'badge' => $this->formatter->status($deal->status),
                ])
                ->all();
            $groups['deals']['total'] = count($groups['deals']['items']);
        }

        if ($this->can($user, 'crm.quotes.view')) {
            $quotes = Quote::query()
                ->with(['company:id,name', 'contact:id,full_name', 'deal:id,title'])
                ->where(function (Builder $query) use ($term): void {
                    $query->where('quote_number', 'like', "%{$term}%")
                        ->orWhereHas('company', fn (Builder $query) => $query->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('contact', fn (Builder $query) => $query->where('full_name', 'like', "%{$term}%"))
                        ->orWhereHas('deal', fn (Builder $query) => $query->where('title', 'like', "%{$term}%"));
                })
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get();

            $groups['quotes']['items'] = $quotes
                ->map(fn (Quote $quote): array => [
                    'title' => $quote->quote_number,
                    'subtitle' => collect([
                        $quote->company?->name ?: $quote->contact?->full_name,
                        $quote->deal?->title,
                        $this->formatter->money($quote->grand_total, $quote->currency),
                    ])->filter()->implode(' / '),
                    'url' => route('crm.quotes.show', $quote),
                    'badge' => $this->formatter->status($quote->status),
                ])
                ->all();
            $groups['quotes']['total'] = count($groups['quotes']['items']);
        }

        return $groups;
    }

    private function can(?Authenticatable $user, string $permission): bool
    {
        return $user ? Gate::forUser($user)->allows($permission) : false;
    }

    /**
     * @return array<string, array{label: string, permission: string, total: int, items: list<array<string, string>>}>
     */
    private function emptyGroups(): array
    {
        return [
            'contacts' => ['label' => __('Contacts'), 'permission' => 'crm.contacts.view', 'total' => 0, 'items' => []],
            'companies' => ['label' => __('Companies'), 'permission' => 'crm.companies.view', 'total' => 0, 'items' => []],
            'deals' => ['label' => __('Deals'), 'permission' => 'crm.deals.view', 'total' => 0, 'items' => []],
            'quotes' => ['label' => __('Quotes'), 'permission' => 'crm.quotes.view', 'total' => 0, 'items' => []],
        ];
    }
}
