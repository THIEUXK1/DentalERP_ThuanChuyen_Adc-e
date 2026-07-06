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
        Schema::table('patient_invoices', function (Blueprint $table) {
            $table->index('patient_id');
            $table->index('branch_id');
            $table->index('due_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_invoices', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['status']);
        });
    }
};
