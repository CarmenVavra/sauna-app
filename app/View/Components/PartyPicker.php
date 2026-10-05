<?php

namespace App\View\Components;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Suchfeld mit Autovervollständigung für die Auswahl einer Partei.
 * Gesucht wird nach Name, Benutzername, Stiege und Top („huber“, „3 7“, „st3 top7“, „3/7“).
 */
class PartyPicker extends Component
{
    /** @var list<array{id: int, label: string, staircase: int, apartment: int, words: string}> */
    public array $options;

    public string $initialLabel;

    /**
     * @param  Collection<int, User>  $parties
     */
    public function __construct(
        Collection $parties,
        public string $id,
        public string $name,
        public string $label = 'Partei',
        public ?string $selected = null,
        public bool $invalid = false,
    ) {
        $this->options = $parties->map(fn (User $p) => [
            'id' => $p->id,
            'label' => 'Stiege '.$p->staircase_number.' / Top '.$p->apartment.' – '.$p->last_name.' '.$p->first_name,
            'staircase' => (int) $p->staircase_number,
            'apartment' => (int) $p->apartment,
            'words' => $p->first_name.' '.$p->last_name.' '.$p->username,
        ])->values()->all();

        $this->initialLabel = collect($this->options)->firstWhere('id', (int) $selected)['label'] ?? '';
        if ($this->initialLabel === '') {
            $this->selected = null;
        }
    }

    public function render(): View
    {
        return view('components.party-picker');
    }
}
