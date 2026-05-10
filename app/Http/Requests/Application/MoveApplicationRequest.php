<?php

declare(strict_types=1);

namespace App\Http\Requests\Application;

use App\Domains\Application\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MoveApplicationRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(ApplicationStatus::values())],
            'after_application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'before_application_id' => ['nullable', 'integer', 'exists:applications,id'],
        ];
    }
}
