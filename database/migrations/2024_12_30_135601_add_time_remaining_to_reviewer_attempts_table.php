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
        Schema::table('reviewer_attempts', function (Blueprint $table) {
            $table->integer('time_remaining')->nullable(); // Adds the column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviewer_attempts', function (Blueprint $table) {
            $table->dropColumn('time_remaining'); // Removes the column in case of rollback
        });
    }
};
