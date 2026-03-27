<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220);
            $table->text('description')->nullable();
            $table->string('source_link', 500)->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->nullable();
            $table->enum('status', ['active', 'completed', 'archived'])->default('active');
            $table->date('first_study_date');
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'first_study_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
