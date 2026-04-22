<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

class ActivitiesController extends CrmResourceController
{
    protected string $module = 'activities';

    protected string $title = 'Activities';

    protected string $permissionPrefix = 'crm.activities';
}
