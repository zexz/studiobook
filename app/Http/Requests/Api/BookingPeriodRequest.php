<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BookingPeriodRequest extends FormRequest
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
            'start_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'days' => [
                'sometimes',
                'integer',
                'min:1',
                'max:365',
            ],
        ];
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'A start date is required',
            'start_date.date_format' => 'The start date must be in the format YYYY-MM-DD',
            'days.integer' => 'The days parameter must be an integer',
            'days.min' => 'The days parameter must be at least 1',
            'days.max' => 'The days parameter cannot exceed 365',
        ];
    }
}
