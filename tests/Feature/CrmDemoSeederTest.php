<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Crm\Database\Seeders\CrmDemoSeeder;
use App\Crm\Models\Activity;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Models\Quote;
use App\Crm\Models\QuoteItem;
use App\Crm\Models\Tag;
use App\Crm\Models\Task as CrmTask;
use Tests\TestCase;

class CrmDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_meaningful_crm_data(): void
    {
        $this->seed(CrmDemoSeeder::class);

        $this->assertSame(7, DealStage::query()->count());
        $this->assertGreaterThanOrEqual(4, Company::query()->count());
        $this->assertGreaterThanOrEqual(8, Contact::query()->count());
        $this->assertGreaterThanOrEqual(8, Deal::query()->count());
        $this->assertGreaterThanOrEqual(8, CrmTask::query()->count());
        $this->assertGreaterThanOrEqual(5, Quote::query()->count());
        $this->assertGreaterThanOrEqual(15, QuoteItem::query()->count());
        $this->assertGreaterThanOrEqual(4, Tag::query()->count());
        $this->assertGreaterThanOrEqual(8, Activity::query()->count());
        $this->assertGreaterThan(0, Deal::query()->won()->count());
        $this->assertGreaterThan(0, Deal::query()->lost()->count());
    }

    public function test_demo_seed_command_is_available(): void
    {
        $this->artisan('crm:seed-demo')
            ->assertSuccessful();

        $this->assertGreaterThanOrEqual(8, Contact::query()->count());
        $this->assertGreaterThanOrEqual(8, Deal::query()->count());
    }
}
