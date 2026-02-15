<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
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
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'slot' => [
                'required',
                'date_format:H:i',
                'regex:/^(09|1[0-9]|20):00$/', // Only allow slots between 09:00 and 20:00, on the hour
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
            'date.required' => 'A date is required',
            'date.date_format' => 'The date must be in the format YYYY-MM-DD',
            'date.after_or_equal' => 'You cannot book dates in the past',
            'slot.required' => 'A time slot is required',
            'slot.date_format' => 'The time slot must be in the format HH:MM',
            'slot.regex' => 'The time slot must be on the hour between 09:00 and 20:00',
        ];
    }
}
