<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_settings', function (Blueprint $table) {
            $table->id();

            $table->time('plan_open_time')->default('07:00');
            $table->time('plan_close_time')->default('10:00');

            $table->time('realization_open_time')->default('15:00');
            $table->time('realization_close_time')->default('23:00');

            $table->date('effective_from')->default(now());
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_settings');
    }
};

