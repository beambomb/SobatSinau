<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'type' => ['nullable', 'string', 'in:announcement,material,discussion'],
            'attachment' => ['nullable', 'file', 'max:20480'], // max 20MB
        ];
    }
}
