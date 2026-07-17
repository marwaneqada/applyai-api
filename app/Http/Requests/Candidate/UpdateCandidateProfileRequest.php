<?php

declare(strict_types=1);

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCandidateProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'headline' => ['sometimes', 'nullable', 'string', 'max:160'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'professional_summary' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'linkedin_url' => ['sometimes', 'nullable', 'url:http,https', 'max:1000'],
            'github_url' => ['sometimes', 'nullable', 'url:http,https', 'max:1000'],
            'portfolio_url' => ['sometimes', 'nullable', 'url:http,https', 'max:1000'],
        ];
    }
}
