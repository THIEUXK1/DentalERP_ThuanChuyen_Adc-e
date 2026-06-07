<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('treatment_plan_id')->nullable()->unique();
            $table->unsignedBigInteger('branch_id');
            $table->string('status')->default('sent');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->bigInteger('total')->default(0);
            $table->bigInteger('amount_paid')->default(0);
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestampsTz();

            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('treatment_plan_id')->references('id')->on('treatment_plans')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_invoices');
    }
};
