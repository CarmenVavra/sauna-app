<?php

namespace App\Http\Requests\User;

use App\Enums\Dosage;
use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDiaryEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reservation = $this->route('reservation');

        return $reservation instanceof Reservation && (bool) $this->user()?->can('writeDiary', $reservation);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coins' => ['required', 'integer', 'min:0', 'max:24'],
            'temperature' => ['required', 'integer', 'min:50', 'max:110'],
            'sessions' => ['required', 'integer', 'min:1', 'max:12'],
            'infusion' => ['required', 'boolean'],
            'dosage' => ['nullable', Rule::enum(Dosage::class)],
            'oil_note' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
            'oils' => ['nullable', 'array', 'max:20'],
            'oils.*.name' => ['required', 'string', 'max:60'],
            'oils.*.drops' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
