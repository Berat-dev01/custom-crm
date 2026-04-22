<?php

namespace Sanalkopru\Crm\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('crm.dashboard.view');

        return view('crm::dashboard.index');
    }
}
