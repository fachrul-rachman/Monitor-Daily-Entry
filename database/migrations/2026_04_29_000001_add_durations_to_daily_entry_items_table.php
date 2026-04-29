<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_entry_items', function (Blueprint $table) {
            $table->unsignedInteger('plan_duration_minutes')->nullable()->after('plan_relation_reason');
            $table->unsignedInteger('realization_duration_minutes')->nullable()->after('realization_reason');
        });
    }

    public function down(): void
    {
        Schema::table('daily_entry_items', function (Blueprint $table) {
            $table->dropColumn(['plan_duration_minutes', 'realization_duration_minutes']);
        });
    }
};

