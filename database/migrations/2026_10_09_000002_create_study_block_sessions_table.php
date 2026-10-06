<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per timer run of a weekly-plan block.
        Schema::create('study_block_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_block_id')->constrained('study_blocks')->cascadeOnDelete();
            // = user_id while the run is running or paused, null once ended:
            // the unique index allows one active run per user.
            $table->unsignedBigInteger('active_user_id')->nullable()->unique();
            $table->dateTime('started_at');
            $table->dateTime('resumed_at')->nullable()->comment('start of the current running stretch; null while paused or ended');
            $table->unsignedInteger('used_seconds')->default(0)->comment('closed stretches');
            $table->dateTime('ended_at')->nullable();
            $table->string('end_reason', 10)->nullable()->comment('finished | stopped');
            $table->timestamps();

            $table->index(['user_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_block_sessions');
    }
};
