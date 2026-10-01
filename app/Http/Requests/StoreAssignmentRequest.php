<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'max_points' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:20480'], // file soal max 20MB
        ];
    }
}
