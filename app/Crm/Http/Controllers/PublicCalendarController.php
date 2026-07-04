<?php

namespace App\Crm\Http\Controllers;

use App\Crm\Services\Calendar\TaskIcsFeed;
use App\Models\User;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class PublicCalendarController extends Controller
{
    public function tasks(string $token, TaskIcsFeed $feed): Response
    {
        abort_unless(strlen($token) >= 32, 404);

        $user = User::query()
            ->where('calendar_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        return response($feed->build($user), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="crm-tasks.ics"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
