<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_requests', 'attachment_path')) {
                $table->text('attachment_path')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('leave_requests', 'attachment_original_name')) {
                $table->string('attachment_original_name')->nullable()->after('attachment_path');
            }
            if (! Schema::hasColumn('leave_requests', 'attachment_mime_type')) {
                $table->string('attachment_mime_type', 191)->nullable()->after('attachment_original_name');
            }
            if (! Schema::hasColumn('leave_requests', 'attachment_size_bytes')) {
                $table->bigInteger('attachment_size_bytes')->nullable()->after('attachment_mime_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            foreach (['attachment_size_bytes', 'attachment_mime_type', 'attachment_original_name', 'attachment_path'] as $col) {
                if (Schema::hasColumn('leave_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

