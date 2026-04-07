<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_entries', function (Blueprint $table) {
            $table->string('plan_title')->nullable()->after('entry_date');
            $table->text('plan_relation_reason')->nullable()->after('plan_text');

            $table->text('realization_notes')->nullable()->after('realization_text');
            $table->text('realization_reason')->nullable()->after('realization_notes');

            $table->foreignId('big_rock_id')
                ->nullable()
                ->after('user_id')
                ->constrained('big_rocks')
                ->nullOnDelete();

            $table->foreignId('roadmap_item_id')
                ->nullable()
                ->after('big_rock_id')
                ->constrained('roadmap_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('roadmap_item_id');
            $table->dropConstrainedForeignId('big_rock_id');

            $table->dropColumn([
                'plan_title',
                'plan_relation_reason',
                'realization_notes',
                'realization_reason',
            ]);
        });
    }
};

