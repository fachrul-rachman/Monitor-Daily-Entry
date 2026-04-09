<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();

            $table->string('channel', 30); // discord, email, whatsapp, etc
            $table->string('type', 50); // daily_summary, reminder_plan, etc
            $table->date('context_date')->nullable(); // tanggal yang diringkas (jika ada)

            $table->string('status', 20)->default('sent'); // sent, failed, skipped
            $table->string('summary', 255)->default('');

            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();

            $table->index(['channel', 'type', 'status']);
            $table->unique(['channel', 'type', 'context_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};

