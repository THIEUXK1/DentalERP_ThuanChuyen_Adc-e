# Phase 1 — Tính năng cốt lõi

Luồng nghiệp vụ chính, theo đúng thứ tự dữ liệu chảy trong hệ thống:

```
Lead/Khách hàng → Đăng ký khám → Lịch hẹn → Khám & chẩn đoán
   → Kế hoạch điều trị (KHDT) → Thực hiện từng bước → Thu ngân / công nợ
   → KPI bác sĩ + Kế toán HKD → Báo cáo
```

## 1. CRM & Khách hàng — `Controllers/Crm`, `Services/{LeadService,PatientMergeService,CareRuleService}`

- Hồ sơ bệnh nhân, nhiều số điện thoại (`patient_phones`), quan hệ gia đình, tệp đính kèm.
- Lead → chuyển đổi thành bệnh nhân; hoạt động chăm sóc, task follow-up, care rule.
- Gộp bệnh nhân trùng qua `PatientMergeService`.
- Dữ liệu di trú từ hệ cũ: `legacy_code`, `legacy_group_key`, `legacy_clinic_record_id`.

## 2. Đăng ký khám & Lịch hẹn — `Controllers/Schedule`, `Services/AppointmentService`

- **Quy tắc đã chốt**: một đăng ký khám chỉ thuộc **đúng ngày hôm đó**; hết ngày tự
  chốt trạng thái "Hoàn thành". Không cho tạo đăng ký ở ngày quá khứ (có lệnh dọn dữ liệu cũ).
- `schedule_registrations` có soft delete, `pending_since`, liên kết `appointment_id`.
- Ghế điều trị (`dental_chairs`), trạng thái ở `Enums/AppointmentStatus`.

## 3. Khám & Kế hoạch điều trị — `Controllers/Clinical`, `Controllers/Dental`, `Services/TreatmentPlanService`

- Khám răng: `dental_examinations` + `dental_examination_conditions` (sơ đồ răng).
- KHDT: `treatment_plans` → `treatment_plan_items` → step executions (`dental_service_steps`).
- Mỗi item có bác sĩ chính + bác sĩ phụ; đổi nhanh trạng thái từng dịch vụ ngay trên danh sách.
- `start_date` là timestamp (ngày **và** giờ điều trị), sửa inline không rời trang.
- Trạng thái: `Enums/{TreatmentPlanStatus,TreatmentItemStatus,TreatmentStepStatus}`.

## 4. Thu ngân & Công nợ — `Controllers/Cashier`, `Services/{InvoiceService,PriceResolver}`

- `patient_invoices` (có trả góp, lý do huỷ), `patient_payments` (gắn `doctor_id`,
  `treatment_plan_item_id`, `reverses_payment_id` cho phiếu hoàn).
- Giá lấy qua `PriceResolver` (bảng giá + bảng giá riêng), **không hardcode giá**.
- Công nợ `patient_debts`; quỹ `fund_accounts` / `fund_transfers`.
- Phiếu thu PDF: `resources/views/pdf/patient-receipt.blade.php`.

## 5. Danh mục — `Controllers/Catalog`

`dental_services` (kèm trường KPI, giá vốn `dental_service_costs`, quy trình bước),
`service_categories`, `service_groups`, `price_lists`.

## 6. Kho & Labo — `Services/InventoryService`, `Controllers/Lab`

- Nhập/xuất kho theo `Enums/InventoryTransactionType`; định mức vật tư theo dịch vụ
  (`inventory_service_templates`).
- Đơn labo, bảo hành labo, bảng giá labo.

## 7. Nhân sự — `Controllers/Hr`, `Services/Hr`, `Services/Payroll*`

- Chấm công: đồng bộ từ máy chấm công (`AttendanceDeviceService`, `ZkSocketService`),
  ký hiệu công, kỳ công, `attendance_audit_logs` (append-only).
- Lương: `PayrollCalculationService`, `PayrollTaxService`, `SalarySlipService`,
  `payroll_audit_logs` (append-only).
- KPI & chất lượng: `Services/Dental/{DentalKpiService,DentalQualityService}`,
  `kpi_allocations`, `kpi_quality_rules`. Doanh thu phân bổ theo bác sĩ **thực thu**.
- Hoa hồng: `CommissionService`.

## 8. Kế toán HKD (TT152) — `Controllers/Hkd`, `Services/Hkd`

Sổ doanh thu / chi phí / kho / quỹ / thuế khác, chốt kỳ (`HkdPeriodCloseService`),
xuất sổ S1a–S3a ra PDF (`resources/views/pdf/hkd-*.blade.php`).
Chế độ kế toán bật/tắt qua `Setting::get('accounting.regime')` — menu ẩn/hiện theo đó.

## 9. Báo cáo — `Controllers/Reports`, `Services/ReportService`

Báo cáo tài chính, "Bảng kế hoạch báo cáo theo ngày" (System Records), xuất Excel có định
dạng qua `app/Exports/`, xuất PDF dashboard.

## 10. Hệ thống — `Controllers/Admin`, `Controllers/Core`

Người dùng/vai trò/quyền, chi nhánh, phòng ban, cài đặt, thông báo, xoá có duyệt
(`pending_deletions`), trang trạng thái server.

---

## Việc còn lại của Phase 1

- Bù test cho các Service tính tiền/lương/KPI (hiện gần như chưa có).
- Rà N+1 và index cho các màn hình danh sách lớn (bệnh nhân, KHDT, System Records).
- Thống nhất định dạng tiền/ngày giữa lớp Vue cũ và lớp Blade mới.

Mọi màn hình chuyển sang Blade phải tuân thủ ràng buộc **View thuần + không reload trang**
trong [../docs/architecture-workflow.md](../docs/architecture-workflow.md).
