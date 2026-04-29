<?php

namespace App\Crm\Http\Requests\Tags;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Crm\Models\Tag;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('crm.tags.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Tag $tag */
        $tag = $this->route('tag');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('tags', 'slug')->ignore($tag->id)->whereNull('deleted_at')],
            'color' => ['required', 'string', 'max:32'],
        ];
    }
}
