<?php

namespace App\Services;

use App\Models\User;

/**
 * Benutzername = „vorname.nachname“ klein, Umlaute → ae/oe/ue/ss,
 * bei Kollision wird eine Zahl angehängt (maria.huber2, …).
 */
class UsernameGenerator
{
    public function base(string $firstName, string $lastName): string
    {
        $name = mb_strtolower(trim($firstName).'.'.trim($lastName));
        $name = strtr($name, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);

        return (string) preg_replace('/[^a-z.]/', '', $name);
    }

    /** Freier Benutzername; der eigene Name von $except zählt nicht als vergeben. */
    public function generate(string $firstName, string $lastName, ?User $except = null): string
    {
        $base = $this->base($firstName, $lastName);
        $taken = User::withTrashed()
            ->where('username', 'like', $base.'%')
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->pluck('username')
            ->flip();

        $candidate = $base;
        for ($k = 2; $taken->has($candidate); $k++) {
            $candidate = $base.$k;
        }

        return $candidate;
    }

    /** Zufälliges Startpasswort, z. B. „sauna-4821“. */
    public function startPassword(): string
    {
        return 'sauna-'.random_int(1000, 9999);
    }
}
