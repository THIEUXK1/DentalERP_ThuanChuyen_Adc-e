<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_clinic_record_id')->nullable()->index()->after('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('patient_payments', function (Blueprint $table) {
            $table->dropColumn('legacy_clinic_record_id');
        });
    }
};
