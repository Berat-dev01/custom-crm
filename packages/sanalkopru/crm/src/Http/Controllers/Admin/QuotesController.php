<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

class QuotesController extends CrmResourceController
{
    protected string $module = 'quotes';

    protected string $title = 'Quotes';

    protected string $permissionPrefix = 'crm.quotes';
}
