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
        Schema::create('rep_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rep_id');
            $table->string('target_type', 50);
            $table->unsignedInteger('target_value');
            $table->unsignedInteger('current_value')->default(0);
            $table->string('period', 50)->nullable();
            $table->timestamps();

            $table->foreign('rep_id')->references('id')->on('medical_reps')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rep_targets');
    }
};
