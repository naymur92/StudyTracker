<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optional break pattern inside a block: `break_minutes` of break after
        // every `break_every_minutes` of work. Both null = no breaks.
        Schema::table('study_blocks', function (Blueprint $table) {
            $table->unsignedSmallInteger('break_every_minutes')->nullable()->after('planned_minutes');
            $table->unsignedSmallInteger('break_minutes')->nullable()->after('break_every_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('study_blocks', function (Blueprint $table) {
            $table->dropColumn(['break_every_minutes', 'break_minutes']);
        });
    }
};
