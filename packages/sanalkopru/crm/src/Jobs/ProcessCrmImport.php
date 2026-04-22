<?php

namespace Sanalkopru\Crm\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Sanalkopru\Crm\Models\CrmImport;
use Sanalkopru\Crm\Services\DataTransfer\CrmDataTransferService;

class ProcessCrmImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public readonly int $importId,
        public readonly ?int $userId = null
    ) {}

    public function handle(CrmDataTransferService $imports): void
    {
        $import = CrmImport::query()->findOrFail($this->importId);
        $user = $this->userId ? User::query()->find($this->userId) : null;

        $imports->process($import, $user);
    }
}
