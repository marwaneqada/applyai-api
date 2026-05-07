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
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('resume_id')
                ->constrained('resumes')
                ->cascadeOnDelete();

            $table->string('job_title');
            $table->string('company_name')->nullable();
            $table->string('job_url', 1000)->nullable();
            $table->longText('job_description');

            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('resume_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};