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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('sender_type'); // doctor, rep
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_doctor_id')->nullable();
            $table->unsignedBigInteger('receiver_rep_id')->nullable();
            $table->text('content');
            $table->boolean('read_status')->default(false);
            $table->timestamps();

            $table->foreign('receiver_doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            $table->foreign('receiver_rep_id')->references('id')->on('medical_reps')->onDelete('cascade');
            $table->index(['sender_type', 'sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
