# Project Scope — Dental ERP

## 1. Trong phạm vi (DO)

| Miền | Nội dung |
|---|---|
| CRM | Bệnh nhân, số điện thoại, quan hệ, tệp đính kèm, lead, chăm sóc, gộp trùng |
| Lịch | Đăng ký khám (chỉ trong ngày), lịch hẹn, ghế điều trị |
| Lâm sàng | Khám răng, sơ đồ răng, chẩn đoán, ghi chú, mẫu lâm sàng |
| Điều trị | Kế hoạch điều trị, dịch vụ theo bước, bác sĩ chính/phụ, trạng thái từng dịch vụ |
| Tài chính | Hoá đơn, trả góp, thanh toán, hoàn tiền, công nợ, quỹ, chi phí |
| Danh mục | Dịch vụ, nhóm/loại dịch vụ, bảng giá, giá vốn |
| Kho & Labo | Nhập xuất tồn, định mức vật tư, đơn labo, bảo hành |
| Nhân sự | Chấm công (máy chấm công), kỳ công, lương, phiếu lương, nghỉ phép, đánh giá |
| KPI | Phân bổ KPI theo bác sĩ thực hiện/thực thu, quy tắc chất lượng, hoa hồng |
| Kế toán | Chế độ hộ kinh doanh TT152: sổ doanh thu/chi phí/kho/quỹ/thuế, chốt kỳ, sổ S1a–S3a |
| Báo cáo | Báo cáo tài chính, báo cáo theo ngày, xuất Excel/PDF |
| Hệ thống | Người dùng, vai trò, quyền, chi nhánh, phòng ban, cài đặt, thông báo, xoá có duyệt |

## 2. Ngoài phạm vi (DON'T)

- **Không** làm cổng thanh toán online / tích hợp ngân hàng tự động.
- **Không** làm app mobile native; web responsive là đủ.
- **Không** làm portal cho bệnh nhân tự đăng nhập (hệ thống dành cho nhân viên phòng khám).
- **Không** làm hồ sơ bệnh án điện tử đạt chuẩn Bộ Y tế / kết nối BHYT nếu chưa có yêu cầu rõ.
- **Không** làm multi-tenant SaaS; hệ thống phục vụ một tổ chức, phân tách theo `branches`.
- **Không** thêm AI/chatbot, realtime websocket, microservice nếu chưa được yêu cầu.
- **Không** làm chức năng marketing automation ngoài phần care rule / message template đã có.

## 3. Ranh giới kỹ thuật

**Được phép:**
- Thêm Controller/Service/Model/Enum/migration trong cấu trúc đã định.
- Chuyển màn hình Vue → Blade + Alpine + JS thuần theo Phase 2.
- Thêm index, tối ưu truy vấn, thêm test.

**Không được phép nếu chưa có quyết định ghi vào Decision Log:**
- Đổi stack (framework, ORM, CSS framework, build tool, DB engine).
- Thêm dependency mới vào `composer.json` / `package.json`.
- Đổi cấu trúc thư mục chuẩn hoặc quy ước đặt tên route/permission.
- Đổi schema bảng đang có dữ liệu thật.
- Đổi quy tắc nghiệp vụ đã chốt (ví dụ: đăng ký khám chỉ thuộc đúng ngày hôm đó).

**Tuyệt đối không:**
- Reload trang cho thao tác nghiệp vụ (xem [architecture-workflow.md](architecture-workflow.md) §3.2).
- Đặt business logic vào View.
- Chạy lệnh ghi dữ liệu lên production khi chưa xác nhận
  (xem [database-safety.md](database-safety.md)).
- Commit / push / deploy khi người dùng không yêu cầu.

## 4. Người dùng hệ thống

Lễ tân, bác sĩ, phụ tá, thu ngân, kho, quản lý nhân sự, kế toán, quản trị viên —
phân quyền qua `spatie/laravel-permission`, dạng `<module>.<action>`.
Mọi tính năng mới **phải** khai báo permission tương ứng và kiểm tra ở **tầng server**,
không chỉ ẩn nút trên UI.
