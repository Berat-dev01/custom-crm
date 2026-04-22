<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

class TagsController extends CrmResourceController
{
    protected string $module = 'tags';

    protected string $title = 'Tags';

    protected string $permissionPrefix = 'crm.tags';
}
