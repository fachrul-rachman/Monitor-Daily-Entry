<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');

            $table->text('plan_text')->nullable();
            $table->string('plan_status', 20)->default('draft'); // draft, submitted, late, missing
            $table->timestamp('plan_submitted_at')->nullable();

            $table->text('realization_text')->nullable();
            $table->string('realization_status', 20)->default('draft'); // draft, submitted, late, missing
            $table->timestamp('realization_submitted_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'entry_date']);
            $table->index(['entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_entries');
    }
};

