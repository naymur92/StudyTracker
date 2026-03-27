<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('study_tasks')->nullOnDelete();
            $table->date('practiced_on');
            $table->enum('practice_type', [
                'problem_solving',
                'implementation',
                'reading',
                'note_making',
                'mock_interview',
                'other',
            ])->default('other');
            $table->text('details');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('outcome', 300)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'practiced_on']);
            $table->index('topic_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_logs');
    }
};
