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
        Schema::create('analysis_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('analysis_id')
                ->constrained('analyses')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('overall_score');
            $table->unsignedTinyInteger('keyword_score');
            $table->unsignedTinyInteger('experience_score');
            $table->unsignedTinyInteger('skills_score');

            $table->json('matched_keywords');
            $table->json('missing_keywords');
            $table->json('strengths');
            $table->json('weaknesses');
            $table->json('gap_analysis');
            $table->json('rewritten_bullets');

            $table->longText('cover_letter');

            $table->longText('raw_ai_response')->nullable();
            $table->string('model_used')->nullable();

            $table->timestamps();

            $table->unique('analysis_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_results');
    }
};