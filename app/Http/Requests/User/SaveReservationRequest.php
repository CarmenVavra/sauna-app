<?php

namespace App\Http\Requests\User;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Neue Reservierung einer Partei oder Änderung einer bestehenden.
 */
class SaveReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reservation = $this->route('reservation');

        return $reservation instanceof Reservation
            ? (bool) $this->user()?->can('update', $reservation)
            : (bool) $this->user() && ! $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'staircase_number' => ['required', 'integer', Rule::exists('staircases', 'number')->where('has_sauna', true)],
            'date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'staircase_number.required' => 'Bitte eine Sauna wählen.',
            'date.required' => 'Bitte einen freien Tag wählen.',
        ];
    }
}
