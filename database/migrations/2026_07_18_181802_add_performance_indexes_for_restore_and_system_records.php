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
        Schema::table('treatment_plan_items', function (Blueprint $table) {
            // Speeds up the SystemRecords unified log join and any other lookup by plan.
            $table->index('treatment_plan_id');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            // Lets the "latest activity per subject" grouping in the Data Restore page use
            // an index-only scan instead of reading every matching row to find MAX(id).
            $table->index(['subject_type', 'subject_id', 'id'], 'activity_log_subject_latest_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treatment_plan_items', function (Blueprint $table) {
            $table->dropIndex(['treatment_plan_id']);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_log_subject_latest_index');
        });
    }
};
