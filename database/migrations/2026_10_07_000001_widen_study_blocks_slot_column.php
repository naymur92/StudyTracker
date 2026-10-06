<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Student templates add the `class_recap` slot (11 characters).
        Schema::table('study_blocks', function (Blueprint $table) {
            $table->string('slot', 20)->comment('morning | class_recap | deep | block_a | block_b | review | minor | other')->change();
        });
    }

    public function down(): void
    {
        Schema::table('study_blocks', function (Blueprint $table) {
            $table->string('slot', 10)->comment('morning | block_a | block_b | review | minor | other')->change();
        });
    }
};
