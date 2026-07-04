<?php

namespace App\Crm\Http\Controllers;

use App\Crm\Services\Dashboard\DashboardReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardReport $dashboard): View
    {
        Gate::authorize('crm.dashboard.view');

        $request->validate([
            'period' => ['nullable', 'string', 'in:today,this_week,this_month,custom'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        return view('crm::dashboard.index', $dashboard->build($request, $request->user()));
    }
}
