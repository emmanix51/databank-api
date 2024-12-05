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
        Schema::table('questions', function (Blueprint $table) {
            //

            $table->unsignedBigInteger('college_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('topic_id')->nullable(); // Nullable topic_id
            $table->unsignedBigInteger('subtopic_id')->nullable(); // Nullable subtopic_id
            $table->text('question_content'); // The content of the question

            // Define the foreign key constraints
            $table->foreign('college_id')->references('id')->on('colleges')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            $table->foreign('topic_id')->references('id')->on('topics')->onDelete('set null'); // If topic is deleted, set to null
            $table->foreign('subtopic_id')->references('id')->on('subtopics')->onDelete('set null'); // If subtopic is deleted, set to null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            //
            $table->dropForeign(['college_id']);
            $table->dropForeign(['program_id']);
            $table->dropForeign(['topic_id']);
            $table->dropForeign(['subtopic_id']);

            // Drop the columns
            $table->dropColumn(['college_id', 'program_id', 'topic_id', 'subtopic_id', 'question_content']);
        });
    }
};
