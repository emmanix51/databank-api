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
        Schema::create('reviewer_attempt_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_attempt_id')->constrained()->onDelete('cascade');
            $table->integer('time_limit');
            $table->unsignedInteger('question_amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviewer_attempt_specifications');
    }
};
