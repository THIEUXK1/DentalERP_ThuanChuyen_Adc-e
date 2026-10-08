<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nhãn chủ số điện thoại ("Bố", "Mẹ"…) để CSKH biết đang gọi cho ai —
     * bệnh nhân nhỏ tuổi thường để số của người nhà. Cột nullable, không backfill.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('phone_label', 30)->nullable()->after('phone');
        });

        Schema::table('patient_phones', function (Blueprint $table) {
            $table->string('label', 30)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('patient_phones', function (Blueprint $table) {
            $table->dropColumn('label');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('phone_label');
        });
    }
};
