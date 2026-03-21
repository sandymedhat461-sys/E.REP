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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('hotline', 20)->nullable();
            $table->string('commercial_register', 100)->unique();
            $table->enum('status', ['pending', 'active', 'blocked'])->default('pending');
            $table->string('company_profile_image', 255)->nullable();
            $table->string('company_id_image', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
