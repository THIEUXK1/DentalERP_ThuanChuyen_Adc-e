<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Postgres doesn't auto-index foreign key columns (unlike MySQL), and these are the
     * biggest, fastest-growing tables in the app whose FK columns were still missing one —
     * every join through them (dashboard, reports, patient page, system-records) was
     * falling back to a full table scan.
     */
    public function up(): void
    {
        Schema::table('treatment_plan_items', function (Blueprint $table) {
            $table->index('service_id');
            $table->index('responsible_doctor_id');
            $table->index('assistant_doctor_id');
            $table->index('examination_id');
        });

        Schema::table('patient_payments', function (Blueprint $table) {
            $table->index('invoice_id');
            $table->index('created_by');
            $table->index('treatment_plan_item_id');
            $table->index('fund_account_id');
        });

        Schema::table('patient_invoices', function (Blueprint $table) {
            $table->index('created_by');
        });

        Schema::table('treatment_plans', function (Blueprint $table) {
            $table->index('appointment_id');
            $table->index('consultant_id');
            $table->index('created_by');
        });

        Schema::table('patient_debts', function (Blueprint $table) {
            $table->index('patient_id');
            $table->index('treatment_plan_id');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->index('created_by');
            $table->index('lead_id');
            $table->index('service_id');
            $table->index('dental_chair_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treatment_plan_items', function (Blueprint $table) {
            $table->dropIndex(['service_id']);
            $table->dropIndex(['responsible_doctor_id']);
            $table->dropIndex(['assistant_doctor_id']);
            $table->dropIndex(['examination_id']);
        });

        Schema::table('patient_payments', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['treatment_plan_item_id']);
            $table->dropIndex(['fund_account_id']);
        });

        Schema::table('patient_invoices', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
        });

        Schema::table('treatment_plans', function (Blueprint $table) {
            $table->dropIndex(['appointment_id']);
            $table->dropIndex(['consultant_id']);
            $table->dropIndex(['created_by']);
        });

        Schema::table('patient_debts', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['treatment_plan_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropIndex(['lead_id']);
            $table->dropIndex(['service_id']);
            $table->dropIndex(['dental_chair_id']);
        });
    }
};
