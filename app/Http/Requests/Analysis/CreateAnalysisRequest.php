<?php

namespace App\Http\Requests\Analysis;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAnalysisRequest extends FormRequest
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
            'resume_id' => ['required', 'integer', 'exists:resumes,id'],
            'job_title' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'job_url' => ['nullable', 'url', 'max:1000'],
            'job_description' => ['required', 'string', 'min:100'],
        ];
    }
}