<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

class ContactsController extends CrmResourceController
{
    protected string $module = 'contacts';

    protected string $title = 'Contacts';

    protected string $permissionPrefix = 'crm.contacts';
}
