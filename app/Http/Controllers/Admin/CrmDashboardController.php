<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class CrmDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.crm.dashboard');
    }
}
