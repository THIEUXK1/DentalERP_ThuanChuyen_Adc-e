<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dời lịch hàng loạt tạo lịch hẹn MỚI thay vì sửa lịch cũ, nên cần con trỏ
     * từ lịch mới về lịch gốc để hai bên đều hiển thị được "đã dời sang / dời từ".
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('rescheduled_from_id')->nullable()->after('lead_id');

            $table->index('rescheduled_from_id');
            $table->foreign('rescheduled_from_id')->references('id')->on('appointments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['rescheduled_from_id']);
            $table->dropIndex(['rescheduled_from_id']);
            $table->dropColumn('rescheduled_from_id');
        });
    }
};
