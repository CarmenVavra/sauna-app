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
        Schema::create('staircases', function (Blueprint $table) {
            $table->unsignedTinyInteger('number')->primary();
            $table->unsignedSmallInteger('apartments')->default(10);
            $table->boolean('has_sauna')->default(false);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 60);
            $table->string('last_name', 60);
            $table->string('username', 80)->unique();
            $table->string('password');
            $table->string('role', 10)->default('user');
            $table->unsignedTinyInteger('staircase_number')->nullable();
            $table->unsignedSmallInteger('apartment')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('staircase_number')->references('number')->on('staircases')->nullOnDelete();
            $table->index(['staircase_number', 'apartment']);
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('staircases');
    }
};
