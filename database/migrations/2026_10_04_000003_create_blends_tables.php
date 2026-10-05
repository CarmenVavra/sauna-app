<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('description', 1000)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('blend_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blend_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('oil_name', 60);
            $table->unsignedTinyInteger('drops');
            $table->string('note', 80)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blend_ingredients');
        Schema::dropIfExists('blends');
    }
};
