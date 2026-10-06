<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->string('gear', 10)->default('green')->comment('green | yellow | red');
            $table->string('major_focus', 200)->nullable();
            $table->string('minor_focus', 200)->nullable();
            $table->text('reflection')->nullable();
            $table->string('if_then_plan', 500)->nullable();
            $table->string('output_note', 300)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week_start']);
        });

        Schema::create('study_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_week_id')->constrained('study_weeks')->cascadeOnDelete();
            $table->date('block_date');
            $table->string('slot', 10)->comment('morning | block_a | block_b | review | minor | other');
            $table->string('lane', 10)->comment('major | minor | review | work');
            $table->string('planned_task', 300)->nullable();
            $table->unsignedSmallInteger('planned_minutes')->nullable();
            $table->string('status', 10)->default('planned')->comment('planned | done | partial | missed | red');
            $table->string('note', 500)->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'block_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_blocks');
        Schema::dropIfExists('study_weeks');
    }
};
