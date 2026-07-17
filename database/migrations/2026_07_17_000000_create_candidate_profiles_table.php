<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('headline')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('location')->nullable();
            $table->text('professional_summary')->nullable();
            $table->string('linkedin_url', 1000)->nullable();
            $table->string('github_url', 1000)->nullable();
            $table->string('portfolio_url', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_profiles');
    }
};
