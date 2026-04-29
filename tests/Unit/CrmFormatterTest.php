<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use App\Crm\Support\CrmFormatter;
use Tests\TestCase;

class CrmFormatterTest extends TestCase
{
    public function test_it_formats_money_and_dates_for_turkish_sales_screens(): void
    {
        $formatter = app(CrmFormatter::class);

        $this->assertSame('1.234,50 TRY', $formatter->money(1234.5, 'TRY'));
        $this->assertSame('23.04.2026', $formatter->date(Carbon::parse('2026-04-23 13:45:00')));
        $this->assertSame('23.04.2026 13:45', $formatter->datetime(Carbon::parse('2026-04-23 13:45:00')));
        $this->assertSame(__('In Progress'), $formatter->status('in_progress'));
    }
}
