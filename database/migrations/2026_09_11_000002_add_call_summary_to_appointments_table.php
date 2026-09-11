<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tóm tắt lần gọi gần nhất ngay trên appointments: bảng lịch hẹn nạp toàn bộ bản ghi
     * một lần, join/subquery sang appointment_call_logs cho mỗi dòng là N+1.
     * Nguồn sự thật vẫn là appointment_call_logs — cột này chỉ để hiển thị.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestampTz('last_call_at')->nullable()->after('notes');
            $table->string('last_call_outcome')->nullable()->after('last_call_at');
            $table->unsignedInteger('call_count')->default(0)->after('last_call_outcome');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['last_call_at', 'last_call_outcome', 'call_count']);
        });
    }
};
