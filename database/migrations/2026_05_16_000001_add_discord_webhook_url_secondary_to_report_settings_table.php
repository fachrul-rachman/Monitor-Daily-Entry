<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('report_settings', 'discord_webhook_url_secondary')) {
                $table->text('discord_webhook_url_secondary')->nullable()->after('discord_webhook_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_settings', function (Blueprint $table) {
            if (Schema::hasColumn('report_settings', 'discord_webhook_url_secondary')) {
                $table->dropColumn('discord_webhook_url_secondary');
            }
        });
    }
};

