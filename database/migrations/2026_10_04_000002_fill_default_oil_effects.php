<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vorhandene Standard-Öle erhalten den Wirkungs-Vorschlag aus config/oil_effects.php.
 * Bereits erfasste Texte bleiben unangetastet.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (config('oil_effects') as $name => $effect) {
            DB::table('oils')->where('name', $name)->whereNull('effect')->update(['effect' => $effect]);
        }
    }

    public function down(): void
    {
        foreach (config('oil_effects') as $name => $effect) {
            DB::table('oils')->where('name', $name)->where('effect', $effect)->update(['effect' => null]);
        }
    }
};
