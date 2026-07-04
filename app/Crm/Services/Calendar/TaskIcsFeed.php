<?php

namespace App\Crm\Services\Calendar;

use App\Crm\Models\Task;
use App\Models\User;
use Carbon\CarbonInterface;

class TaskIcsFeed
{
    /**
     * Build an ICS calendar of the user's open tasks with due dates.
     */
    public function build(User $user): string
    {
        $tasks = Task::query()
            ->where('assigned_to', $user->id)
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now()->subDays(30))
            ->where('due_at', '<=', now()->addDays(365))
            ->orderBy('due_at')
            ->limit(500)
            ->get();

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//CRM Engine//Tasks//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escape(config('app.name', 'CRM').' Tasks'),
        ];

        foreach ($tasks as $task) {
            $lines = [...$lines, ...$this->event($task)];
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * @return list<string>
     */
    private function event(Task $task): array
    {
        $host = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'crm.local';

        $lines = [
            'BEGIN:VEVENT',
            'UID:crm-task-'.$task->id.'@'.$host,
            'DTSTAMP:'.$this->timestamp($task->updated_at ?? now()),
            'DTSTART:'.$this->timestamp($task->due_at),
            'DTEND:'.$this->timestamp($task->due_at->copy()->addMinutes(30)),
            'SUMMARY:'.$this->escape($task->title),
            'STATUS:'.($task->status === 'completed' ? 'CONFIRMED' : 'TENTATIVE'),
        ];

        if ($task->description) {
            $lines[] = 'DESCRIPTION:'.$this->escape(str($task->description)->limit(500));
        }

        $lines[] = 'URL:'.route('crm.tasks.show', $task);
        $lines[] = 'END:VEVENT';

        return $lines;
    }

    private function timestamp(CarbonInterface $moment): string
    {
        return $moment->copy()->utc()->format('Ymd\THis\Z');
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n"],
            ['\\\\', '\;', '\,', '\n', '\n'],
            $value
        );
    }
}
