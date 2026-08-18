<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKeywordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => 'required|string|max:255',
            'cluster' => 'nullable|string|max:255',
            'intent'  => 'nullable|in:informational,commercial,transactional,navigational',
        ];
    }
}