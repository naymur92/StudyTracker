<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // Recall card
            $table->json('recall_questions')->nullable()->after('notes');
            $table->text('summary')->nullable()->after('recall_questions');
            $table->string('practice_prompt', 500)->nullable()->after('summary');
            $table->string('lane', 10)->nullable()->after('practice_prompt')->comment('major | minor | work');

            // Mistake entries
            $table->string('kind', 10)->default('topic')->after('lane')->comment('topic | mistake');
            $table->foreignId('parent_topic_id')->nullable()->after('kind')->constrained('topics')->nullOnDelete();
            $table->json('mistake_details')->nullable()->after('parent_topic_id');
            $table->timestamp('merged_at')->nullable()->after('mistake_details');

            // Adaptive schedule state
            $table->unsignedTinyInteger('srs_step')->default(0)->after('merged_at');
            $table->unsignedSmallInteger('srs_lapses')->default(0)->after('srs_step');
            $table->date('last_reviewed_on')->nullable()->after('srs_lapses');

            // Schedule snapshot
            $table->json('srs_offsets')->nullable()->after('last_reviewed_on');
            $table->unsignedSmallInteger('srs_repeat_every_days')->nullable()->after('srs_offsets');
            $table->date('srs_repeat_until')->nullable()->after('srs_repeat_every_days');
            $table->string('srs_schedule_source', 30)->nullable()->after('srs_repeat_until');

            $table->index(['user_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'kind']);
            $table->dropConstrainedForeignId('parent_topic_id');
            $table->dropColumn([
                'recall_questions', 'summary', 'practice_prompt', 'lane',
                'kind', 'mistake_details', 'merged_at',
                'srs_step', 'srs_lapses', 'last_reviewed_on',
                'srs_offsets', 'srs_repeat_every_days', 'srs_repeat_until', 'srs_schedule_source',
            ]);
        });
    }
};
