<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tooth_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('tooth_number');
            $table->string('condition');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestampsTz();

            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('recorded_by')->references('id')->on('users');

            // One condition record per tooth per patient (upsert pattern)
            $table->unique(['patient_id', 'tooth_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tooth_conditions');
    }
};
