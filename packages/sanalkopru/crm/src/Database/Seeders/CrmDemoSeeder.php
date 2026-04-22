<?php

namespace Sanalkopru\Crm\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Sanalkopru\Crm\Models\Activity;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\QuoteItem;
use Sanalkopru\Crm\Models\Tag;
use Sanalkopru\Crm\Models\Task;

class CrmDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CrmPermissionSeeder::class);

        $users = collect([
            $this->user('CRM Owner', 'crm.owner@example.com', 'crm_owner'),
            $this->user('Sales Manager', 'crm.manager@example.com', 'crm_manager'),
            $this->user('Sales Rep', 'crm.sales@example.com', 'crm_sales'),
            $this->user('Support Agent', 'crm.support@example.com', 'crm_support'),
            $this->user('CRM Viewer', 'crm.viewer@example.com', 'crm_viewer'),
        ]);

        $stages = collect($this->seedDealStages());
        $tags = collect($this->seedTags());

        $companies = collect([
            Company::factory()->named('Nova Teknoloji A.S.')->create(['owner_id' => $users[0]->id]),
            Company::factory()->named('Atlas Perakende Ltd.')->create(['owner_id' => $users[1]->id]),
            Company::factory()->named('Mavi Uretim Sanayi')->create(['owner_id' => $users[2]->id]),
            Company::factory()->named('Kuzey Danismanlik')->create(['owner_id' => $users[1]->id]),
        ]);

        $contacts = $companies->flatMap(function (Company $company) use ($users) {
            return Contact::factory()
                ->count(2)
                ->create([
                    'company_id' => $company->id,
                    'owner_id' => $users->random()->id,
                ]);
        });

        $deals = $contacts->take(6)->values()->map(function (Contact $contact, int $index) use ($stages, $users) {
            $stage = $stages[$index % max(1, $stages->count() - 2)];

            return Deal::factory()->create([
                'title' => $contact->company->name.' CRM Kurulumu',
                'contact_id' => $contact->id,
                'company_id' => $contact->company_id,
                'stage_id' => $stage->id,
                'owner_id' => $users->random()->id,
                'position' => $index + 1,
                'probability' => $stage->probability,
            ]);
        });

        $wonStage = $stages->firstWhere('slug', 'won');
        $lostStage = $stages->firstWhere('slug', 'lost');

        $deals->push(Deal::factory()->won()->create([
            'stage_id' => $wonStage->id,
            'contact_id' => $contacts[0]->id,
            'company_id' => $contacts[0]->company_id,
            'owner_id' => $users[0]->id,
            'position' => 1,
        ]));

        $deals->push(Deal::factory()->lost()->create([
            'stage_id' => $lostStage->id,
            'contact_id' => $contacts[1]->id,
            'company_id' => $contacts[1]->company_id,
            'owner_id' => $users[1]->id,
            'position' => 1,
        ]));

        $deals->each(function (Deal $deal) use ($tags, $users): void {
            $deal->tags()->syncWithoutDetaching($tags->random(2)->pluck('id')->all());

            Task::factory()->create([
                'taskable_type' => $deal::class,
                'taskable_id' => $deal->id,
                'assigned_to' => $deal->owner_id,
                'title' => $deal->title.' icin takip',
            ]);

            Activity::factory()->create([
                'activityable_type' => $deal::class,
                'activityable_id' => $deal->id,
                'user_id' => $users->random()->id,
                'subject' => 'Pipeline guncellendi',
                'type' => 'system',
            ]);
        });

        $contacts->each(function (Contact $contact) use ($tags): void {
            $contact->tags()->syncWithoutDetaching($tags->random(1)->pluck('id')->all());
        });

        $deals->take(5)->each(function (Deal $deal, int $index) use ($users): void {
            $quote = Quote::factory()->create([
                'quote_number' => sprintf('%s%06d', config('crm.quotes.number_prefix', 'CRM-'), $index + 1),
                'contact_id' => $deal->contact_id,
                'company_id' => $deal->company_id,
                'deal_id' => $deal->id,
                'owner_id' => $users->random()->id,
                'status' => $index === 0 ? 'accepted' : 'sent',
                'accepted_at' => $index === 0 ? now() : null,
            ]);

            QuoteItem::factory()->count(3)->create(['quote_id' => $quote->id]);

            Activity::factory()->create([
                'activityable_type' => $quote::class,
                'activityable_id' => $quote->id,
                'user_id' => $quote->owner_id,
                'subject' => 'Teklif hazirlandi',
                'type' => 'email',
            ]);
        });
    }

    private function user(string $name, string $email, string $role): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (method_exists($user, 'assignRole') && ! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }

    /**
     * @return list<DealStage>
     */
    private function seedDealStages(): array
    {
        $stages = [
            ['name' => 'Yeni', 'slug' => 'new', 'color' => '#64748b', 'position' => 1, 'probability' => 10],
            ['name' => 'Nitelikli', 'slug' => 'qualified', 'color' => '#2563eb', 'position' => 2, 'probability' => 30],
            ['name' => 'Teklif', 'slug' => 'proposal', 'color' => '#7c3aed', 'position' => 3, 'probability' => 55],
            ['name' => 'Pazarlik', 'slug' => 'negotiation', 'color' => '#f59e0b', 'position' => 4, 'probability' => 75],
            ['name' => 'Kazanildi', 'slug' => 'won', 'color' => '#16a34a', 'position' => 5, 'probability' => 100, 'is_won' => true],
            ['name' => 'Kaybedildi', 'slug' => 'lost', 'color' => '#dc2626', 'position' => 6, 'probability' => 0, 'is_lost' => true],
        ];

        return collect($stages)
            ->map(fn (array $stage): DealStage => DealStage::query()->updateOrCreate(
                ['slug' => $stage['slug']],
                $stage + ['is_won' => false, 'is_lost' => false]
            ))
            ->all();
    }

    /**
     * @return list<Tag>
     */
    private function seedTags(): array
    {
        return collect([
            ['name' => 'VIP', 'slug' => 'vip', 'color' => '#7c3aed'],
            ['name' => 'Sicak Firsat', 'slug' => 'hot-lead', 'color' => '#dc2626'],
            ['name' => 'Kurumsal', 'slug' => 'enterprise', 'color' => '#2563eb'],
            ['name' => 'Takip', 'slug' => 'follow-up', 'color' => '#f59e0b'],
        ])
            ->map(fn (array $tag): Tag => Tag::query()->updateOrCreate(['slug' => $tag['slug']], $tag))
            ->all();
    }
}
