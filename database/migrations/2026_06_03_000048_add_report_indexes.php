<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_payments', function (Blueprint $table) {
            $table->index('payment_date');
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->index('status');
        });
        Schema::table('patient_debts', function (Blueprint $table) {
            $table->index('status');
        });
        Schema::table('leads', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('patient_payments', fn ($t) => $t->dropIndex(['payment_date']));
        Schema::table('appointments', fn ($t) => $t->dropIndex(['status']));
        Schema::table('patient_debts', fn ($t) => $t->dropIndex(['status']));
        Schema::table('leads', fn ($t) => $t->dropIndex(['status']));
    }
};
