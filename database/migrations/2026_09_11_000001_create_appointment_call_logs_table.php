<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('outcome');
            $table->string('phone')->nullable();
            $table->text('note')->nullable();
            $table->timestampTz('called_at');
            $table->unsignedBigInteger('created_by');
            $table->timestampsTz();

            $table->index(['appointment_id', 'called_at']);
            $table->index(['patient_id', 'called_at']);

            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_call_logs');
    }
};
