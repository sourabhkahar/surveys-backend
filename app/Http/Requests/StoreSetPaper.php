<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSetPaper extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required',
            'sections' => 'required|array|min:1',
            'sections.*.title' => 'required|string',
            'sections.*.section_type' => 'required|string',
            'sections.*.questions' => 'required|array|min:1',
            'sections.*.questions.*.question' => 'required|string',
            'sections.*.questions.*.type' => 'required|in:text,select,checkbox,radio',
            'sections.*.questions.*.options' => 'nullable',
        ];
    }
}
