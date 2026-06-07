<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_debts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('treatment_plan_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->unique();
            $table->bigInteger('amount')->default(0);
            $table->bigInteger('paid_amount')->default(0);
            $table->bigInteger('remaining')->default(0);
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestampsTz();

            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('treatment_plan_id')->references('id')->on('treatment_plans')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('patient_invoices');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_debts');
    }
};
