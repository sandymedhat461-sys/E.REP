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
        DB::statement("ALTER TABLE drug_samples MODIFY COLUMN status ENUM('pending','approved','rejected','delivered') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE drug_samples MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'");
    }
};
