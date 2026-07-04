<?php

namespace App\Crm\Database\Seeders;

use App\Crm\Models\DealStage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmPerformanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CrmDealStageSeeder::class);

        $owners = $this->owners();
        $stages = DealStage::query()->ordered()->get(['id', 'probability', 'is_won', 'is_lost']);
        $now = now();

        $companyIds = $this->insertCompanies($owners, $now);
        $contactIds = $this->insertContacts($owners, $companyIds, $now);
        $this->insertDeals($owners, $companyIds, $contactIds, $stages, $now);
    }

    /**
     * @return list<int>
     */
    private function owners(): array
    {
        return collect(range(1, 10))
            ->map(fn (int $number): int => User::query()->firstOrCreate(
                ['email' => 'perf.user.'.$number.'@example.test'],
                [
                    'name' => 'Perf User '.$number,
                    'password' => 'password',
                ]
            )->id)
            ->all();
    }

    /**
     * @param  list<int>  $owners
     * @return list<int>
     */
    private function insertCompanies(array $owners, mixed $now): array
    {
        foreach (array_chunk(range(1, 2000), 500) as $chunk) {
            DB::table('companies')->insert(array_map(fn (int $number): array => [
                'public_id' => (string) Str::uuid(),
                'name' => 'Performance Company '.$number,
                'email' => 'company'.$number.'@perf.test',
                'phone' => '+900000'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                'website' => 'https://company'.$number.'.perf.test',
                'tax_number' => str_pad((string) $number, 10, '0', STR_PAD_LEFT),
                'tax_office' => 'Performance',
                'city' => 'Istanbul',
                'country' => 'TR',
                'sector' => ['Technology', 'Retail', 'Manufacturing', 'Consulting'][$number % 4],
                'owner_id' => $owners[$number % count($owners)],
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        return DB::table('companies')->where('name', 'like', 'Performance Company %')->pluck('id')->all();
    }

    /**
     * @param  list<int>  $owners
     * @param  list<int>  $companyIds
     * @return list<int>
     */
    private function insertContacts(array $owners, array $companyIds, mixed $now): array
    {
        foreach (array_chunk(range(1, 10000), 1000) as $chunk) {
            DB::table('contacts')->insert(array_map(fn (int $number): array => [
                'public_id' => (string) Str::uuid(),
                'first_name' => 'Perf',
                'last_name' => 'Contact '.$number,
                'full_name' => 'Perf Contact '.$number,
                'email' => 'contact'.$number.'@perf.test',
                'phone' => '+901111'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                'title' => 'Buyer',
                'company_id' => $companyIds[$number % count($companyIds)],
                'lifecycle_stage' => ['lead', 'prospect', 'customer'][$number % 3],
                'source' => ['website', 'referral', 'event', 'outbound'][$number % 4],
                'owner_id' => $owners[$number % count($owners)],
                'last_contacted_at' => $now->copy()->subDays($number % 90),
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        return DB::table('contacts')->where('full_name', 'like', 'Perf Contact %')->pluck('id')->all();
    }

    /**
     * @param  list<int>  $owners
     * @param  list<int>  $companyIds
     * @param  list<int>  $contactIds
     */
    private function insertDeals(array $owners, array $companyIds, array $contactIds, mixed $stages, mixed $now): void
    {
        foreach (array_chunk(range(1, 5000), 500) as $chunk) {
            DB::table('deals')->insert(array_map(function (int $number) use ($owners, $companyIds, $contactIds, $stages, $now): array {
                $stage = $stages[$number % $stages->count()];
                $status = $stage->is_won ? 'won' : ($stage->is_lost ? 'lost' : 'open');

                return [
                    'public_id' => (string) Str::uuid(),
                    'title' => 'Performance Deal '.$number,
                    'contact_id' => $contactIds[$number % count($contactIds)],
                    'company_id' => $companyIds[$number % count($companyIds)],
                    'stage_id' => $stage->id,
                    'value' => 1000 + ($number * 17),
                    'currency' => 'TRY',
                    'probability' => $stage->probability,
                    'expected_close_date' => $now->copy()->addDays($number % 120)->toDateString(),
                    'closed_at' => $status === 'open' ? null : $now->copy()->subDays($number % 60),
                    'status' => $status,
                    'lost_reason' => $status === 'lost' ? 'Performance sample' : null,
                    'owner_id' => $owners[$number % count($owners)],
                    'position' => $number,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $chunk));
        }
    }
}
