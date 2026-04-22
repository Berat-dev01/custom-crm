<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sanalkopru\Crm\Database\Seeders\CrmDemoSeeder;
use Sanalkopru\Crm\Models\Activity;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\QuoteItem;
use Sanalkopru\Crm\Models\Tag;
use Sanalkopru\Crm\Models\Task as CrmTask;
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
}
