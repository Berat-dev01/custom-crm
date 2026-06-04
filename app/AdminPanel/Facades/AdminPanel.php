<?php

namespace App\AdminPanel\Facades;

use Illuminate\Support\Facades\Facade;

class AdminPanel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'admin-panel';
    }
}
