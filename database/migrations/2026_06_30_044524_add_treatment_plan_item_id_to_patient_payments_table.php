<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patient_payments', function (Blueprint $table) {
            $table->foreignId('treatment_plan_item_id')
                  ->nullable()
                  ->after('invoice_id')
                  ->constrained('treatment_plan_items')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_payments', function (Blueprint $table) {
            $table->dropForeign(['treatment_plan_item_id']);
            $table->dropColumn('treatment_plan_item_id');
        });
    }
};
