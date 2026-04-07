<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_scores', function (Blueprint $table) {
            $table->id();

            $table->date('score_date');

            // scope_type: company | division | user
            $table->string('scope_type', 20);
            $table->unsignedBigInteger('scope_id')->nullable();

            $table->unsignedSmallInteger('score'); // 0-100
            $table->json('components')->nullable();

            $table->timestamps();

            $table->unique(['score_date', 'scope_type', 'scope_id']);
            $table->index(['score_date']);
            $table->index(['scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_scores');
    }
};

