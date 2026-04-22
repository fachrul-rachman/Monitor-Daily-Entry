<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->unsignedSmallInteger('year')->index();

            // Source flags (mirrors API fields so filtering rules remain explicit).
            $table->boolean('is_holiday')->default(true);
            $table->boolean('is_joint_holiday')->default(false);
            $table->boolean('is_observance')->default(false);

            // Keep the upstream id if provided.
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};

