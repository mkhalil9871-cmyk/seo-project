<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'domain'   => 'required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'country'  => 'nullable|string|max:100',
            'language' => 'nullable|string|max:50',
        ];
    }
}