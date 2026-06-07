<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('treatment_plan_id')->nullable();
            $table->integer('amount');       // VND
            $table->string('period', 7);     // YYYY-MM
            $table->string('status')->default('pending'); // pending|approved|paid
            $table->timestampsTz();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('patient_invoices')->onDelete('cascade');
            $table->index(['employee_id', 'period']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_transactions');
    }
};
