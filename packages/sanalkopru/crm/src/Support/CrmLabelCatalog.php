<?php

namespace Sanalkopru\Crm\Support;

use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Services\Authorization\PermissionCatalog;

class CrmLabelCatalog
{
    public function __construct(private readonly PermissionCatalog $permissions) {}

    /**
     * @return list<array{label: string, icon: string, items: list<array{label: string, route: string, permission: string}>}>
     */
    public function navigationGroups(): array
    {
        return [
            [
                'label' => __('Overview'),
                'icon' => 'layout-dashboard',
                'items' => [
                    ['label' => __('Dashboard'), 'route' => 'crm.dashboard', 'permission' => 'crm.dashboard.view'],
                    ['label' => __('Search'), 'route' => 'crm.search', 'permission' => 'crm.dashboard.view'],
                ],
            ],
            [
                'label' => __('Sales'),
                'icon' => 'badge-dollar-sign',
                'items' => [
                    ['label' => __('Deals'), 'route' => 'crm.deals.index', 'permission' => 'crm.deals.view'],
                    ['label' => __('Quotes'), 'route' => 'crm.quotes.index', 'permission' => 'crm.quotes.view'],
                    ['label' => __('Deal Stages'), 'route' => 'crm.deal-stages.index', 'permission' => 'crm.settings.manage'],
                ],
            ],
            [
                'label' => __('Customers'),
                'icon' => 'users',
                'items' => [
                    ['label' => __('Contacts'), 'route' => 'crm.contacts.index', 'permission' => 'crm.contacts.view'],
                    ['label' => __('Companies'), 'route' => 'crm.companies.index', 'permission' => 'crm.companies.view'],
                ],
            ],
            [
                'label' => __('Operations'),
                'icon' => 'clipboard-check',
                'items' => [
                    ['label' => __('Tasks'), 'route' => 'crm.tasks.index', 'permission' => 'crm.tasks.view'],
                    ['label' => __('Activities'), 'route' => 'crm.activities.index', 'permission' => 'crm.activities.view'],
                    ['label' => __('Tags'), 'route' => 'crm.tags.index', 'permission' => 'crm.tags.view'],
                ],
            ],
            [
                'label' => __('System'),
                'icon' => 'settings',
                'items' => [
                    ['label' => __('Users'), 'route' => 'crm.users.index', 'permission' => 'crm.users.manage'],
                    ['label' => __('Settings'), 'route' => 'crm.settings.index', 'permission' => 'crm.settings.manage'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function lifecycleStages(): array
    {
        return [
            'lead' => __('Lead'),
            'prospect' => __('Prospect'),
            'customer' => __('Customer'),
            'inactive' => __('Inactive'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function contactSources(): array
    {
        return [
            'website' => __('Website'),
            'referral' => __('Referral'),
            'event' => __('Event'),
            'outbound' => __('Outbound'),
            'partner' => __('Partner'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function dealStatuses(): array
    {
        return [
            'open' => __('Open'),
            'won' => __('Won'),
            'lost' => __('Lost'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function quoteStatuses(): array
    {
        return [
            'draft' => __('Draft'),
            'sent' => __('Sent'),
            'accepted' => __('Accepted'),
            'rejected' => __('Rejected'),
            'expired' => __('Expired'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function taskPriorities(): array
    {
        return [
            'low' => __('Low'),
            'normal' => __('Normal'),
            'high' => __('High'),
            'urgent' => __('Urgent'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function taskStatuses(): array
    {
        return [
            'open' => __('Open'),
            'in_progress' => __('In Progress'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function activityTypes(): array
    {
        return [
            'note' => __('Note'),
            'call' => __('Call'),
            'email' => __('Email'),
            'meeting' => __('Meeting'),
            'task_completed' => __('Task Completed'),
            'quote_sent' => __('Quote Sent'),
            'deal_moved' => __('Stage Change'),
            'deal_won' => __('Deal Won'),
            'deal_lost' => __('Deal Lost'),
            'system' => __('System'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function relatedRecordTypes(): array
    {
        return [
            'contact' => __('Contact'),
            'company' => __('Company'),
            'deal' => __('Deal'),
            'quote' => __('Quote'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function discountTypes(): array
    {
        return [
            'fixed' => __('Fixed'),
            'percentage' => __('Percentage'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function savedFilterVisibilities(): array
    {
        return [
            'private' => __('Private'),
            'public' => __('Public'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function crmRoles(): array
    {
        return collect($this->permissions->roles())
            ->mapWithKeys(fn (array $role, string $key): array => [$key => $this->roleLabel($key)])
            ->all();
    }

    public function moduleLabel(string $module): string
    {
        return match ($module) {
            'contacts' => __('Contacts'),
            'companies' => __('Companies'),
            'deals' => __('Deals'),
            'tasks' => __('Tasks'),
            'quotes' => __('Quotes'),
            'activities' => __('Activities'),
            'tags' => __('Tags'),
            'users' => __('Users'),
            default => __((string) str($module)->replace('_', ' ')->headline()),
        };
    }

    public function relatedRecordTypeKeyFromModel(?string $modelClass): ?string
    {
        return match ($modelClass) {
            Contact::class => 'contact',
            Company::class => 'company',
            Deal::class => 'deal',
            Quote::class => 'quote',
            default => null,
        };
    }

    public function relatedRecordTypeLabelFromModel(?string $modelClass): ?string
    {
        $key = $this->relatedRecordTypeKeyFromModel($modelClass);

        return $key ? ($this->relatedRecordTypes()[$key] ?? $this->status($key)) : null;
    }

    public function status(string $value): string
    {
        $labels = [
            ...$this->lifecycleStages(),
            ...$this->dealStatuses(),
            ...$this->quoteStatuses(),
            ...$this->taskPriorities(),
            ...$this->taskStatuses(),
            ...$this->activityTypes(),
            ...$this->contactSources(),
            ...$this->discountTypes(),
        ];

        if (isset($labels[$value])) {
            return $labels[$value];
        }

        return __((string) str($value)->replace('_', ' ')->headline());
    }

    public function roleLabel(string $key): string
    {
        return match ($key) {
            'owner' => __('Owner'),
            'manager' => __('Manager'),
            'sales' => __('Sales'),
            'support' => __('Support'),
            'viewer' => __('Viewer'),
            default => __((string) str($key)->replace('_', ' ')->headline()),
        };
    }
}
