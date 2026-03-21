<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * DB already updated via phpMyAdmin — migration for version control only.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE messages ADD CONSTRAINT messages_receiver_check CHECK (receiver_doctor_id IS NOT NULL OR receiver_rep_id IS NOT NULL)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE messages DROP CHECK messages_receiver_check');
    }
};
