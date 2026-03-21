<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * DB already updated via phpMyAdmin — migration for version control only.
     */
    public function up(): void
    {
        Schema::table('doctor_points', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id')->nullable()->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_points', function (Blueprint $table) {
            $table->dropColumn('source_id');
        });
    }
};
