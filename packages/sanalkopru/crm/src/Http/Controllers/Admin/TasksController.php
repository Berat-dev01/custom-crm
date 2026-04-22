<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

class TasksController extends CrmResourceController
{
    protected string $module = 'tasks';

    protected string $title = 'Tasks';

    protected string $permissionPrefix = 'crm.tasks';
}
