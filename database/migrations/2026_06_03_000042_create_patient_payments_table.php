<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->bigInteger('amount');
            $table->string('method');
            $table->date('payment_date');
            $table->string('reference')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestampsTz();

            $table->foreign('invoice_id')->references('id')->on('patient_invoices');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_payments');
    }
};
