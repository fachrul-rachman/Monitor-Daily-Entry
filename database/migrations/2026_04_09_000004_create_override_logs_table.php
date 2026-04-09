<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('override_logs', function (Blueprint $table) {
            $table->id();

            // Siapa yang melakukan override (MVP: hanya Admin)
            $table->unsignedBigInteger('actor_user_id');

            // Target yang di-override (MVP: daily_entries)
            $table->string('target_type', 50); // daily_entries, daily_entry_items (future)
            $table->unsignedBigInteger('target_id');

            // Konteks tanggal (untuk daily entry)
            $table->date('context_date')->nullable();

            // Alasan wajib
            $table->text('reason');

            // Perubahan (before/after)
            $table->json('changes');

            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['context_date']);

            $table->foreign('actor_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('override_logs');
    }
};

