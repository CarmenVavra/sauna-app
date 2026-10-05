<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReservationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in([
                ReservationStatus::Issued->value,
                ReservationStatus::Returned->value,
                ReservationStatus::Cancelled->value,
            ])],
        ];
    }

    public function targetStatus(): ReservationStatus
    {
        return ReservationStatus::from((string) $this->validated('status'));
    }
}
