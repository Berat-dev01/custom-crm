<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Task;
use Tests\TestCase;

class CrmCalendarFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->user = User::factory()->create()->assignRole('crm_sales');
        $this->user->forceFill(['calendar_token' => Str::random(48)])->save();
    }

    public function test_feed_returns_assigned_tasks_as_ics(): void
    {
        Task::factory()->create([
            'assigned_to' => $this->user->id,
            'title' => 'Müşteri araması; önemli',
            'due_at' => now()->addDays(2),
        ]);
        Task::factory()->create([
            'assigned_to' => User::factory()->create()->id,
            'title' => 'Baskasinin gorevi',
            'due_at' => now()->addDays(2),
        ]);

        $response = $this->get(route('crm.public.calendar.tasks', $this->user->calendar_token))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8');

        $ics = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('SUMMARY:Müşteri araması\; önemli', $ics);
        $this->assertStringNotContainsString('Baskasinin gorevi', $ics);
    }

    public function test_invalid_token_returns_404(): void
    {
        $this->get('/calendar/'.Str::random(48).'/tasks.ics')->assertNotFound();
        $this->get('/calendar/short/tasks.ics')->assertNotFound();
    }

    public function test_inactive_user_feed_is_rejected(): void
    {
        $this->user->forceFill(['is_active' => false])->save();

        $this->get(route('crm.public.calendar.tasks', $this->user->calendar_token))
            ->assertNotFound();
    }

    public function test_user_can_regenerate_calendar_token(): void
    {
        $oldToken = $this->user->calendar_token;

        $this->actingAs($this->user, 'admin')
            ->post(route('crm.security.calendar-token'))
            ->assertRedirect(route('crm.security.index'));

        $newToken = $this->user->refresh()->calendar_token;
        $this->assertNotSame($oldToken, $newToken);

        $this->get(route('crm.public.calendar.tasks', $oldToken))->assertNotFound();
        $this->get(route('crm.public.calendar.tasks', $newToken))->assertOk();
    }
}
