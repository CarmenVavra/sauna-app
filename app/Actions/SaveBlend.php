<?php

namespace App\Actions;

use App\Models\Blend;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Legt eine Mischung an oder ändert sie. Öle der Mischung, die noch nicht in
 * „Meine Öle“ stehen, werden dort ergänzt – so sind sie im Tagebuch wählbar und
 * ihre Wirkung lässt sich pflegen.
 */
class SaveBlend
{
    /**
     * @param  array{name: string, description?: string|null, ingredients: list<array{oil_name: string, drops: int|string, note?: string|null}>}  $data
     * @return array{blend: Blend, addedOils: list<string>}
     */
    public function handle(User $user, array $data, ?Blend $blend = null, ?string $brandForNewOils = null): array
    {
        return DB::transaction(function () use ($user, $data, $blend, $brandForNewOils) {
            $existing = $user->oils()->pluck('name')->keyBy(fn (string $name) => mb_strtolower($name));
            $addedOils = [];

            $ingredients = [];
            foreach ($data['ingredients'] as $k => $i) {
                $name = trim($i['oil_name']);
                $known = $existing->get(mb_strtolower($name));
                if ($known === null) {
                    $user->oils()->create([
                        'name' => $name,
                        'brand' => $brandForNewOils,
                        'effect' => config('oil_effects')[$name] ?? null,
                    ]);
                    $existing->put(mb_strtolower($name), $name);
                    $addedOils[] = $name;
                }
                $ingredients[] = [
                    'position' => $k + 1,
                    'oil_name' => $known ?? $name,
                    'drops' => (int) $i['drops'],
                    'note' => isset($i['note']) && trim((string) $i['note']) !== '' ? trim((string) $i['note']) : null,
                ];
            }

            $attributes = ['name' => trim($data['name']), 'description' => $data['description'] ?? null];
            if ($blend) {
                $blend->update($attributes);
                $blend->ingredients()->delete();
            } else {
                $blend = $user->blends()->create($attributes);
            }
            $blend->ingredients()->createMany($ingredients);
            $blend->load('ingredients');

            return ['blend' => $blend, 'addedOils' => $addedOils];
        });
    }
}
