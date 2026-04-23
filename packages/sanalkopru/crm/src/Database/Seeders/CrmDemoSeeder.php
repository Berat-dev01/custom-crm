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
        $this->call(CrmDealStageSeeder::class);

        $users = collect([
            $this->user('CRM Owner', 'crm.owner@example.com', 'crm_owner'),
            $this->user('Sales Manager', 'crm.manager@example.com', 'crm_manager'),
            $this->user('Sales Rep', 'crm.sales@example.com', 'crm_sales'),
            $this->user('Support Agent', 'crm.support@example.com', 'crm_support'),
            $this->user('CRM Viewer', 'crm.viewer@example.com', 'crm_viewer'),
        ]);

        $stages = DealStage::query()->ordered()->get();
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
                'created_at' => now()->subDays($index * 3),
                'updated_at' => now()->subDays($index),
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
            'closed_at' => now(),
            'created_at' => now()->subDays(9),
            'updated_at' => now(),
        ]));

        $deals->push(Deal::factory()->lost()->create([
            'stage_id' => $lostStage->id,
            'contact_id' => $contacts[1]->id,
            'company_id' => $contacts[1]->company_id,
            'owner_id' => $users[1]->id,
            'position' => 1,
            'closed_at' => now()->subDays(18),
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(18),
        ]));

        $deals->values()->each(function (Deal $deal, int $index) use ($tags, $users): void {
            $deal->tags()->syncWithoutDetaching($tags->random(2)->pluck('id')->all());

            Task::factory()->create([
                'taskable_type' => $deal::class,
                'taskable_id' => $deal->id,
                'assigned_to' => $deal->owner_id,
                'title' => $deal->title.' icin takip',
                'due_at' => now()->addDays($index + 1),
                'reminder_at' => now()->addDays($index + 1)->subHour(),
            ]);

            Activity::factory()->create([
                'activityable_type' => $deal::class,
                'activityable_id' => $deal->id,
                'user_id' => $users->random()->id,
                'subject' => 'Pipeline guncellendi',
                'type' => 'system',
                'occurred_at' => now()->subDays($index * 2),
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
                'created_at' => now()->subDays($index * 7),
                'updated_at' => now()->subDays($index * 7),
            ]);

            QuoteItem::factory()->count(3)->create(['quote_id' => $quote->id]);

            Activity::factory()->create([
                'activityable_type' => $quote::class,
                'activityable_id' => $quote->id,
                'user_id' => $quote->owner_id,
                'subject' => 'Teklif hazirlandi',
                'type' => 'email',
                'occurred_at' => now()->subDays($index * 7),
            ]);
        });
    }

    private function user(string $name, string $email, string $role): User
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = new User;
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ])->save();
        }

        if (method_exists($user, 'assignRole') && ! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
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
