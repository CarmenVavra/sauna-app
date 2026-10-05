<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Staircase;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStaircaseRequest extends FormRequest
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
            'apartments' => ['required', 'integer', 'min:1', 'max:40'],
            'has_sauna' => ['required', 'boolean'],
            'confirm_cancel' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Wohnungen mit Parteien dürfen nicht wegfallen; Sauna deaktivieren nur mit Bestätigung,
     * wenn noch künftige Reservierungen bestehen.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                /** @var Staircase $staircase */
                $staircase = $this->route('staircase');

                $highest = (int) $staircase->parties()->max('apartment');
                if ((int) $this->input('apartments') < $highest) {
                    $validator->errors()->add('apartments', 'In Top '.$highest.' ist noch eine Partei registriert.');
                }

                if ($staircase->has_sauna && ! $this->boolean('has_sauna') && ! $this->boolean('confirm_cancel')) {
                    $open = $this->futureReservations($staircase);
                    if ($open > 0) {
                        $validator->errors()->add('has_sauna', 'Für diese Sauna bestehen noch '.$open.' künftige Reservierungen. Zum Deaktivieren bestätigen – sie werden dann storniert.');
                    }
                }
            },
        ];
    }

    public function futureReservations(Staircase $staircase): int
    {
        return Reservation::query()
            ->where('status', ReservationStatus::Reserved->value)
            ->where('staircase_number', $staircase->number)
            ->where('date', '>=', CarbonImmutable::today()->toDateString())
            ->count();
    }
}
