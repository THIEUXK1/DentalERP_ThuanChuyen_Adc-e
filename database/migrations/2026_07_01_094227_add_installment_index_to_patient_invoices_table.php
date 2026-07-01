<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_invoices', function (Blueprint $table) {
            // Drop unique so a plan can have multiple installment invoices
            $table->dropUnique(['treatment_plan_id']);
            $table->index('treatment_plan_id');

            // Which installment this invoice represents (null = single full invoice)
            $table->unsignedSmallInteger('installment_index')->nullable()->after('treatment_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('patient_invoices', function (Blueprint $table) {
            $table->dropIndex(['treatment_plan_id']);
            $table->dropColumn('installment_index');
            $table->unique('treatment_plan_id');
        });
    }
};
