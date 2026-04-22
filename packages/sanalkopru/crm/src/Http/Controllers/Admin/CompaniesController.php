<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

class CompaniesController extends CrmResourceController
{
    protected string $module = 'companies';

    protected string $title = 'Companies';

    protected string $permissionPrefix = 'crm.companies';
}
