<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coin_price' => ['required', 'numeric', 'min:0', 'max:999'],
            'minutes_per_coin' => ['required', 'integer', 'min:5', 'max:600'],
            'coins_total' => ['required', 'integer', 'min:0', 'max:100000'],
            'mon_thu_from' => ['required', 'date_format:H:i'],
            'mon_thu_until' => ['required', 'date_format:H:i', 'after:mon_thu_from'],
            'fri_from' => ['required', 'date_format:H:i'],
            'fri_until' => ['required', 'date_format:H:i', 'after:fri_from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mon_thu_until.after' => 'Die Ausgabe Mo–Do muss nach dem Beginn enden.',
            'fri_until.after' => 'Die Ausgabe am Freitag muss nach dem Beginn enden.',
        ];
    }
}
