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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('paper_id');
            $table->text('section_name');
            $table->text('total_marks')->nullable();
            $table->text('caption')->nullable();
            $table->enum ('section_type', ['question_answer', 'mcqs', 'matching', 'truefalse', 'fillintheblank', 'drawing'])
            ->default('question_answer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
