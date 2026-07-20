<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('reverses_payment_id')->nullable()->after('created_by');
            $table->foreign('reverses_payment_id')->references('id')->on('patient_payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_payments', function (Blueprint $table) {
            $table->dropForeign(['reverses_payment_id']);
            $table->dropColumn('reverses_payment_id');
        });
    }
};
