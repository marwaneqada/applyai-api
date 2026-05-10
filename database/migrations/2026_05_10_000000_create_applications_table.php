<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('analysis_id')
                ->nullable()
                ->constrained('analyses')
                ->nullOnDelete();

            $table->string('company_name');
            $table->string('job_title');
            $table->string('job_url', 1000)->nullable();
            $table->string('status')
                ->default('saved')
                ->comment('saved|applied|interview|offer|rejected');
            $table->date('applied_date')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('notes')->nullable();
            $table->float('position')->default(1);
            $table->timestamps();

            $table->index(['user_id', 'status', 'position']);
            $table->index('analysis_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
