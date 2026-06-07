<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('consultant_id')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('status')->default('draft');
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('deposit_amount')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestampTz('approved_at')->nullable();
            $table->timestampsTz();

            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('doctor_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('consultant_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plans');
    }
};
