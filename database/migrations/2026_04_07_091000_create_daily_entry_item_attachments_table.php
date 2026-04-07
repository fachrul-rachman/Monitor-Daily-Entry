<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_entry_item_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_entry_item_id')
                ->constrained('daily_entry_items')
                ->cascadeOnDelete();

            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->timestamps();

            $table->index(['daily_entry_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_entry_item_attachments');
    }
};

