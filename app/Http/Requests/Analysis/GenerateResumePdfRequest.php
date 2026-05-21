<?php

declare(strict_types=1);

namespace App\Http\Requests\Analysis;

use App\Domains\Resume\Enums\ResumePdfTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GenerateResumePdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template' => ['required', 'string', Rule::in(ResumePdfTemplate::values())],
        ];
    }
}
