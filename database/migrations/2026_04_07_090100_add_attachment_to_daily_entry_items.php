<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_entry_items', function (Blueprint $table) {
            $table->string('realization_attachment_path')->nullable()->after('realization_reason');
        });
    }

    public function down(): void
    {
        Schema::table('daily_entry_items', function (Blueprint $table) {
            $table->dropColumn('realization_attachment_path');
        });
    }
};

