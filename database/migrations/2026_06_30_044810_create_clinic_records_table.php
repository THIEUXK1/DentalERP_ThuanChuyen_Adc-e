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
        Schema::create('clinic_records', function (Blueprint $table) {
            $table->id();                                                           // STT → id tự sinh

            // Thời gian
            $table->date('record_date')->comment('Ngày');
            $table->time('record_time')->nullable()->comment('Giờ');

            // Khách hàng
            $table->string('patient_name')->comment('Tên khách hàng');
            $table->string('patient_code', 50)->nullable()->comment('Mã KH');

            // Loại phát sinh
            $table->string('record_type', 50)->comment('Phát sinh: Thủ thuật / Thanh toán');

            // Dịch vụ
            $table->string('service_name')->nullable()->comment('Dịch vụ/thủ thuật');
            $table->unsignedBigInteger('unit_price')->default(0)->comment('Đơn giá');
            $table->unsignedInteger('quantity')->default(1)->comment('SL');
            $table->unsignedBigInteger('discount')->default(0)->comment('K.Mại');
            $table->unsignedBigInteger('amount')->default(0)->comment('Thành tiền');

            // Tài chính
            $table->unsignedBigInteger('total_collected')->default(0)->comment('Tổng đồng thu');
            $table->unsignedBigInteger('remaining_debt')->default(0)->comment('Còn nợ');
            $table->unsignedBigInteger('collected_this_period')->default(0)->comment('Thu trong ký');
            $table->string('fund_name')->nullable()->comment('Nguồn quỹ');

            // Tiến trình điều trị
            $table->string('treatment_step')->nullable()->comment('Bước tiến trình');
            $table->text('treatment_step_notes')->nullable()->comment('Nội dung tiến trình');

            // Nhân sự
            $table->string('consultant_name', 100)->nullable()->comment('Tư vấn');
            $table->string('doctor_name', 100)->nullable()->comment('Bác sĩ');
            $table->string('assistant_name', 100)->nullable()->comment('Trợ thủ');

            // Thông tin bệnh nhân
            $table->unsignedSmallInteger('birth_year')->nullable()->comment('N.S');
            $table->string('gender', 10)->nullable()->comment('G.T');
            $table->string('phone', 20)->nullable()->comment('Điện thoại');
            $table->string('customer_source')->nullable()->comment('Nguồn khách');

            // Lâm sàng
            $table->text('symptoms')->nullable()->comment('Triệu chứng');
            $table->text('diagnosis')->nullable()->comment('Chẩn đoán');
            $table->string('service_group')->nullable()->comment('Nhóm thủ thuật');
            $table->string('status', 100)->nullable()->comment('Trạng thái');

            $table->timestamps();
            $table->softDeletes();

            $table->index('record_date');
            $table->index('patient_code');
            $table->index('record_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_records');
    }
};
