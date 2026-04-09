<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('division_id')->nullable();

            // business: "Cuti Tahunan", "Izin Sakit", "Izin Pribadi", dst (string agar fleksibel).
            $table->string('type', 50);

            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();

            // pending | approved | rejected | cancelled
            $table->string('status', 20)->default('pending')->index();

            // Audit keputusan
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'start_date', 'end_date']);
            $table->index(['division_id', 'start_date', 'end_date']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('division_id')->references('id')->on('divisions')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};

