<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_revision_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()
                ->comment('NULL = system default template');
            $table->string('name', 100)->comment('e.g. Revision 1');
            $table->unsignedSmallInteger('day_offset')->comment('Days after first_study_date: 1, 7, 30, 90');
            $table->unsignedTinyInteger('sequence_no')->comment('1, 2, 3, 4');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_revision_templates');
    }
};
