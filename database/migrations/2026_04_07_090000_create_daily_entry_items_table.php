<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_entry_id')
                ->constrained('daily_entries')
                ->cascadeOnDelete();

            $table->foreignId('big_rock_id')
                ->nullable()
                ->constrained('big_rocks')
                ->nullOnDelete();

            $table->foreignId('roadmap_item_id')
                ->nullable()
                ->constrained('roadmap_items')
                ->nullOnDelete();

            $table->string('plan_title');
            $table->text('plan_text')->nullable();
            $table->text('plan_relation_reason');

            $table->string('realization_status', 20)->default('draft'); // draft, done, partial, not_done, blocked
            $table->text('realization_text')->nullable();
            $table->text('realization_reason')->nullable();

            $table->timestamps();

            $table->index(['daily_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_entry_items');
    }
};

