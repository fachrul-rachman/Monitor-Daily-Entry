<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_settings', function (Blueprint $table) {
            $table->boolean('discord_enabled')->default(false)->after('is_active');
            $table->time('discord_summary_time')->default('20:00')->after('discord_enabled');
            $table->text('discord_webhook_url')->nullable()->after('discord_summary_time');
        });
    }

    public function down(): void
    {
        Schema::table('report_settings', function (Blueprint $table) {
            $table->dropColumn(['discord_webhook_url', 'discord_summary_time', 'discord_enabled']);
        });
    }
};

