<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('dental_chair_id')->nullable();
            $table->date('registration_date');
            $table->time('visit_time')->nullable();
            // pending | in_treatment | completed | cancelled
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestampsTz();

            $table->index(['branch_id', 'registration_date']);
            $table->index(['doctor_id', 'registration_date']);

            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('doctor_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('dental_chair_id')->references('id')->on('dental_chairs')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_registrations');
    }
};
