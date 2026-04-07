<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table) {
            $table->id();

            $table->date('finding_date');

            // scope_type: company | division | user
            $table->string('scope_type', 20);
            $table->unsignedBigInteger('scope_id')->nullable();

            // Actor/context (optional, but useful for "siapa yang melakukan")
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();

            // type contoh: late_weekly, missing_daily, repetitive_5days
            $table->string('type', 50);

            // low | medium | high
            $table->string('severity', 10);

            $table->string('title');
            $table->text('description')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['finding_date']);
            $table->index(['scope_type', 'scope_id']);
            $table->index(['severity']);
            $table->index(['type']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};

