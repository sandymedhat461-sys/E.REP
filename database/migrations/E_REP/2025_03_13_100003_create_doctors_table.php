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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone', 20)->unique();
            $table->string('national_id', 20)->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('specialization', 100)->index();
            $table->string('hospital_name')->nullable();
            $table->enum('status', ['pending', 'active', 'blocked'])->default('pending');
            $table->string('syndicate_id', 50)->nullable();
            $table->string('profile_image', 255)->nullable();
            $table->string('syndicate_id_image', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
