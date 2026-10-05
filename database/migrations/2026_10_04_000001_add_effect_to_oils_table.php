<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oils', function (Blueprint $table) {
            $table->text('effect')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('oils', function (Blueprint $table) {
            $table->dropColumn('effect');
        });
    }
};
