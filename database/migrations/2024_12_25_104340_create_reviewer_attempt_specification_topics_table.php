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
        Schema::create('reviewer_attempt_specification_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_attempt_specification_id')
                ->constrained('reviewer_attempt_specifications', 'id')
                ->onDelete('cascade')
                ->name('ras_topics_fk');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviewer_attempt_specification_topic');
    }
};
