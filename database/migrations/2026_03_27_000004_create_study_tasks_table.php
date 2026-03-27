<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->enum('task_type', ['learn', 'revision', 'practice', 'custom'])->default('learn');
            $table->unsignedTinyInteger('revision_no')->nullable()->comment('1-4 for revision tasks');
            $table->string('title', 300);
            $table->date('scheduled_date');
            $table->enum('status', ['pending', 'completed', 'skipped', 'missed'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->boolean('is_date_locked')->default(false)
                ->comment('True after completion — scheduled_date becomes immutable');
            $table->foreignId('parent_task_id')->nullable()->constrained('study_tasks')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->enum('difficulty_feedback', ['easy', 'medium', 'hard'])->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scheduled_date', 'status']);
            $table->index(['topic_id', 'task_type']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_tasks');
    }
};
