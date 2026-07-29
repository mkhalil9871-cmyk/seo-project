<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'sometimes|required|string|max:255',
            'domain'   => 'sometimes|required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'country'  => 'nullable|string|max:100',
            'language' => 'nullable|string|max:50',
        ];
    }
}