<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(){
        $storedDate = Carbon::parse($this->expire_date);
        $this->merge([
            'user_id' => $this->user()->id,
            'expire_date' => $storedDate
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'=>'required|string|max:1000',
            'user_id'=>'exists:users,id',
            'image'=>'nullable:string',
            'status'=>'required|boolean',
            'description'=>'nullable|string',
            'expire_date'=>'nullable|date|after:tomorrow',
            'questions'=>'array'
        ];
    }
}
