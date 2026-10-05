<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('coin_price', 8, 2);
            $table->unsignedSmallInteger('minutes_per_coin');
            $table->unsignedInteger('coins_total');
            $table->string('mon_thu_from', 5);
            $table->string('mon_thu_until', 5);
            $table->string('fri_from', 5);
            $table->string('fri_until', 5);
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('staircase_number');
            $table->date('date');
            $table->boolean('is_weekend')->default(false);
            $table->string('status', 12)->default('reserviert');
            $table->unsignedSmallInteger('coins_collected')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('staircase_number')->references('number')->on('staircases')->cascadeOnDelete();
            $table->index(['staircase_number', 'date']);
            $table->index(['user_id', 'date']);
            $table->index('status');
        });

        Schema::create('coin_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('sold_on');
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 8, 2);
            $table->timestamps();

            $table->index('sold_on');
        });

        Schema::create('coin_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('purchased_on');
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 8, 2);
            $table->timestamps();
        });

        Schema::create('oils', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('brand', 60)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('diary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('coins');
            $table->unsignedTinyInteger('temperature');
            $table->unsignedTinyInteger('sessions');
            $table->boolean('infusion')->default(false);
            $table->string('dosage', 12)->nullable();
            $table->string('oil_note', 500)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('diary_entry_oils', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diary_entry_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedTinyInteger('drops');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diary_entry_oils');
        Schema::dropIfExists('diary_entries');
        Schema::dropIfExists('oils');
        Schema::dropIfExists('coin_purchases');
        Schema::dropIfExists('coin_sales');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('settings');
    }
};
