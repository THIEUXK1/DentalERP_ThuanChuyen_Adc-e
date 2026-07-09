<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->index('branch_id');
            $table->index('deleted_at');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->index('patient_id');
            $table->index(['status', 'scheduled_at']);
        });

        Schema::table('schedule_registrations', function (Blueprint $table) {
            $table->index('patient_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['status', 'scheduled_at']);
        });

        Schema::table('schedule_registrations', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['status']);
        });
    }
};
