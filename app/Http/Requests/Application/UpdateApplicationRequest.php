<?php

declare(strict_types=1);

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateApplicationRequest extends FormRequest
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
            'analysis_id' => ['sometimes', 'nullable', 'integer', 'exists:analyses,id'],
            'company_name' => ['sometimes', 'required', 'string', 'max:255'],
            'job_title' => ['sometimes', 'required', 'string', 'max:255'],
            'job_url' => ['sometimes', 'nullable', 'url', 'max:1000'],
            'applied_date' => ['sometimes', 'nullable', 'date'],
            'contact_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
