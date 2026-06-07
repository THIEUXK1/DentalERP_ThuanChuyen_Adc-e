<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('dental_chair_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->timestampTz('scheduled_at');
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->string('status')->default('booked');
            $table->string('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestampsTz();

            $table->index(['branch_id', 'scheduled_at']);
            $table->index(['doctor_id', 'scheduled_at']);

            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('doctor_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('dental_chair_id')->references('id')->on('dental_chairs')->nullOnDelete();
            $table->foreign('service_id')->references('id')->on('dental_services')->nullOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
