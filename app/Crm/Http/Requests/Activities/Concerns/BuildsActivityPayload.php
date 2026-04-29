<?php

namespace App\Crm\Http\Requests\Activities\Concerns;

use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;

trait BuildsActivityPayload
{
    /**
     * @return array{activityable: object, payload: array<string, mixed>}
     */
    public function activityData(): array
    {
        $validated = $this->validated();
        $activityable = $this->activityableClass($validated['activityable_type'])::query()
            ->findOrFail($validated['activityable_id']);

        unset($validated['activityable_type'], $validated['activityable_id']);

        return [
            'activityable' => $activityable,
            'payload' => $validated,
        ];
    }

    private function activityableClass(string $type): string
    {
        return match ($type) {
            'contact' => Contact::class,
            'company' => Company::class,
            'deal' => Deal::class,
            'quote' => Quote::class,
        };
    }
}
