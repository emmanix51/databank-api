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
        Schema::create('reviewers', function (Blueprint $table) {
            $table->id();
            $table->string('reviewer_name');
            $table->text('reviewer_description');
            
            $table->foreignId('topic_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('subtopic_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->foreignId('college_id')->constrained()->onDelete('cascade');

            $table->integer('school_year')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviewers');
    }
};
