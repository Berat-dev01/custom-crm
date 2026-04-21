<?php

namespace Sanalkopru\Crm\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('crm::dashboard.index');
    }
}
