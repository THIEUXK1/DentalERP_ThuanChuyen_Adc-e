<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('treatment_plan_id');
            $table->unsignedBigInteger('service_id');
            $table->string('name');
            $table->string('tooth_number')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->bigInteger('unit_price');
            $table->bigInteger('subtotal');
            $table->string('status')->default('pending');
            $table->string('notes')->nullable();
            $table->timestampsTz();

            $table->foreign('treatment_plan_id')->references('id')->on('treatment_plans')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('dental_services');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_items');
    }
};
