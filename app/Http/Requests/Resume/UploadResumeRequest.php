<?php

namespace App\Http\Requests\Resume;

use Illuminate\Foundation\Http\FormRequest;

final class UploadResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resume' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }
}