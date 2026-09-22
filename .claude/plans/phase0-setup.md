# Phase 0 — Khởi tạo khung dự án & Database

Trạng thái: **đã hoàn thành**. File này ghi lại nền móng đang có, để không dựng lại
hoặc phá vỡ nhầm khi làm các phase sau.

## 0.1 Khung ứng dụng

- Laravel ^12, PHP ^8.2, PSR-4: `App\` → `app/`.
- Laravel Breeze (Inertia + Vue) sinh sẵn auth: `routes/auth.php`, `app/Http/Controllers/Auth/`,
  `app/Http/Requests/Auth/`.
- Vite 7 + Tailwind 3 + `@tailwindcss/forms`; entry `resources/css/app.css`, `resources/js/`.
- Alpine.js dùng ở lớp Blade mới, nạp trực tiếp từ `public/js/alpine.min.js` (không qua Vite).
- `App\Support\Asset::version()` dùng để cache-bust asset tĩnh của lớp Blade.

## 0.2 Phân quyền & audit

- `spatie/laravel-permission`: permission dạng chuỗi `<module>.<action>`
  (`patients.view`, `appointments.view`, `treatment_plans.view`, `reports.financial`…).
  Danh sách thực tế tra trong `config/menu.php` và `routes/web.php`.
- `spatie/laravel-activitylog`: nhật ký hoạt động; các bảng `*_audit_logs`
  (`attendance_audit_logs`, `payroll_audit_logs`) là **append-only**.
- Sanctum cho API token; Ziggy expose route name sang JS.

## 0.3 Database

- Migration nằm ở `database/migrations/`, đặt tên theo timestamp Laravel chuẩn.
- Các nhóm bảng chính:
  - **CRM / bệnh nhân**: `patients`, `patient_phones`, `patient_relationships`,
    `patient_attachments`, `leads`, `contact_activities`, `follow_up_tasks`.
  - **Lịch**: `appointments`, `schedule_registrations` (có `deleted_at`, `pending_since`,
    `appointment_id`), `dental_chairs`.
  - **Điều trị**: `treatment_plans`, `treatment_plan_items` (+ step executions),
    `dental_examinations`, `dental_conditions`, `clinical_notes`, `clinical_records`.
  - **Tài chính**: `patient_invoices`, `patient_payments` (có `reverses_payment_id`,
    `doctor_id`), `patient_debts`, `fund_accounts`, `fund_transfers`, `expenses`.
  - **Danh mục**: `dental_services`, `service_categories`, `service_groups`,
    `price_lists`, `price_list_items`.
  - **Kho / Labo**: `inventory_items`, `inventory_transactions`,
    `inventory_service_templates`, `labs`, `lab_orders`, `lab_warranties`, `lab_price_items`.
  - **HR**: `employees`, `employee_contracts`, `attendance_*`, `payrolls`, `payroll_items`,
    `payroll_settings`, `salary_slips`, `leave_requests`, `performance_reviews`,
    `kpi_allocations`, `kpi_quality_rules`.
  - **Kế toán HKD (TT152)**: `hkd_profiles`, `hkd_categories`, `hkd_tax_rates`,
    `hkd_revenue_entries`, `hkd_expense_entries`, `hkd_inventory_*`, `hkd_cash_*`,
    `hkd_other_taxes`, `hkd_period_closes`, `hkd_documents`.
  - **Hệ thống**: `settings`, `branches`, `departments`, `pending_deletions`,
    `app_notifications`, `message_logs`, `message_templates`.
- Soft delete dùng `deleted_at`; xoá có duyệt đi qua `pending_deletions`.
- Index hiệu năng bổ sung bằng migration riêng (`add_indexes_*`,
  `add_missing_fk_indexes_on_large_tables`) — **không sửa migration cũ đã chạy**.

## 0.4 Enum trạng thái

`app/Enums/` (~50 file) là nguồn sự thật cho mọi trạng thái nghiệp vụ. Khi cần thêm trạng
thái mới: sửa enum + migration đổi cột (nếu là cột enum DB) + cập nhật chỗ hiển thị —
**không hardcode chuỗi trạng thái trong Controller/View**.

## 0.5 Môi trường Dev — bắt buộc có Hot Reload

```bash
php artisan serve                      # backend  http://127.0.0.1:8000
npm run dev -- --clearScreen false     # Vite dev server + HMR (chạy nền)
php vendor/bin/pint --quiet            # format PHP
```

- Hot reload đã sẵn sàng: `vite.config.js` bật `laravel({ refresh: true })` và
  `server: { host: '127.0.0.1', port: 5173 }` → sửa `resources/js/**`, `resources/css/**`
  là trình duyệt tự cập nhật, **không cần F5**.
- Khi lớp Blade lớn dần, mở rộng `refresh` để theo dõi thêm view và JS thuần:

  ```js
  laravel({
      input: 'resources/js/app.js',
      refresh: ['resources/views/**', 'public/js/**', 'routes/**'],
  })
  ```

  Thay đổi cấu hình build phải ghi vào Decision Log trước khi áp dụng
  (xem [../docs/project-scope.md](../docs/project-scope.md) §3).
- **Luôn kèm `--clearScreen false`** khi chạy Vite: mặc định Vite xoá màn hình terminal mỗi
  lần rebuild, làm mất lịch sử lỗi của phiên làm việc.
- Blade sinh thẻ asset qua `@vite(...)`; khi dev server đang chạy, Laravel đọc `public/hot`
  và trỏ thẳng sang `127.0.0.1:5173`. Trước khi deploy phải `npm run build`.

Không dùng Laragon GUI. `.env` local **đang trỏ DB production** — đọc
[../docs/database-safety.md](../docs/database-safety.md) trước khi chạy bất kỳ lệnh nào
có ghi dữ liệu.
