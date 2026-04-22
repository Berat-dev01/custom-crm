<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DealsController extends CrmResourceController
{
    protected string $module = 'deals';

    protected string $title = 'Deals';

    protected string $permissionPrefix = 'crm.deals';

    public function move(string $deal): JsonResponse
    {
        $this->authorizeAction('move');

        return response()->json([
            'message' => 'Deal move endpoint is registered and awaits pipeline implementation.',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }
}
