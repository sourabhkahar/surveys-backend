<?php

use Illuminate\Support\Facades\DB;
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
        Schema::table('sections', function (Blueprint $table) {
             DB::statement("ALTER TABLE `sections` MODIFY COLUMN `section_type` ENUM('mcqs', 'fillintheblank', 'question_answer', 'matching', 'drawing', 'single_image','truefalse') NOT NULL DEFAULT 'mcqs'");
        });
    }

    /** 
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            DB::statement("ALTER TABLE `sections` MODIFY COLUMN `section_type` ENUM('mcqs', 'fillintheblank', 'question_answer', 'matching', 'drawing', 'truefalse') NOT NULL DEFAULT 'mcqs'");
        });
    }
};
