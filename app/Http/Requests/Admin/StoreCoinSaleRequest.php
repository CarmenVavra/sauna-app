<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\Setting;
use App\Services\CoinLedger;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCoinSaleRequest extends FormRequest
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
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', Role::User->value)->whereNull('deleted_at')],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'sold_on' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Bitte eine Partei wählen.',
            'quantity.required' => 'Bitte eine Anzahl eingeben.',
            'quantity.min' => 'Bitte eine Anzahl eingeben.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $stock = app(CoinLedger::class)->summary(Setting::current(), CarbonImmutable::today()->year)['stock'];
                if ((int) $this->input('quantity') > $stock) {
                    $validator->errors()->add('quantity', 'Nur noch '.max(0, $stock).' Münzen im Lager.');
                }
            },
        ];
    }
}
