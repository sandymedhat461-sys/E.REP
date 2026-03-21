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
        DB::statement("ALTER TABLE rep_targets MODIFY COLUMN target_type ENUM('meetings','samples','reviews','events','doctors') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE rep_targets MODIFY COLUMN target_type VARCHAR(50) NOT NULL');
    }
};
