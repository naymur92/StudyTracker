<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_tasks', function (Blueprint $table) {
            $table->string('recall_grade', 10)->nullable()->after('difficulty_feedback')->comment('again | hard | good | easy');
            $table->unsignedSmallInteger('review_seconds')->nullable()->after('recall_grade');
            $table->string('review_kind', 10)->nullable()->after('review_seconds')->comment('step | relearn | repeat');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('study_preferences')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('study_tasks', function (Blueprint $table) {
            $table->dropColumn(['recall_grade', 'review_seconds', 'review_kind']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('study_preferences');
        });
    }
};
