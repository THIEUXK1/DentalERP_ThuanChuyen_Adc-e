# Master Plan — Dental ERP

> Dự án chưa có `CLAUDE.md`, chưa có `docs/` hay ADR. File này là nguồn tham chiếu
> kiến trúc/lộ trình chính cho tới khi có tài liệu chính thức. Mọi số liệu dưới đây
> lấy từ codebase thực tế (composer.json, package.json, `app/`, `routes/web.php`).

## 1. Sản phẩm

Hệ thống ERP cho phòng khám nha khoa: CRM khách hàng, đăng ký khám & lịch hẹn, kế hoạch
điều trị, thu ngân/công nợ, kho vật tư, labo, nhân sự (chấm công – lương – KPI), kế toán
hộ kinh doanh (TT152/HKD) và báo cáo.

## 2. Stack thực tế

| Lớp | Công nghệ |
|---|---|
| Backend | PHP ^8.2, Laravel ^12 |
| Auth / phân quyền | Laravel Breeze, Sanctum, `spatie/laravel-permission` |
| Audit | `spatie/laravel-activitylog` |
| Xuất file | `maatwebsite/excel`, `barryvdh/laravel-dompdf` (views `resources/views/pdf/`) |
| Frontend hiện tại | Inertia 2 + Vue 3 (`resources/js/Pages`, ~140 trang) + Tailwind 3 + Vite 7 |
| Frontend đích | Blade + Alpine.js 3 + JavaScript thuần (`resources/views/`, `public/js/`) |
| Route helper | Ziggy |
| DB | MySQL (xem `config/database.php`, `.env`) |

**Stack đã chốt — không tự ý đổi.** Không thêm SPA framework mới, không đổi ORM, không
thay Tailwind, không thêm build tool ngoài Vite mà chưa có quyết định ghi lại ở
[00-context-memory.md](00-context-memory.md).

## 3. Cấu trúc code (đang có)

```
app/Http/Controllers/{Accounting,Admin,Auth,Cashier,Catalog,Clinical,Core,
                      Crm,Dental,Hkd,Hr,Lab,Reports,Schedule}/   88 controller
app/Services/            business logic (+ Dental/, Hkd/, Hr/)
app/Models/              Eloquent (+ Concerns/ trait dùng chung)
app/Enums/               ~50 enum trạng thái nghiệp vụ
app/Support/             helper thuần: Asset, Icons, Menu, heroicons
app/Http/Requests/       validation
app/Jobs/  app/Exports/  app/Console/Commands/
config/menu.php          cấu hình menu sidebar (bản PHP của menuConfig.js)
resources/views/         layouts/ partials/ components/ pdf/   ← lớp Blade mới
resources/js/            Pages/ Components/ Layouts/ composables/ utils/  ← lớp Vue cũ
routes/web.php           603 dòng — nguồn sự thật về endpoint
```

## 4. Lộ trình

| Giai đoạn | Nội dung | Trạng thái |
|---|---|---|
| Phase 0 | Khung dự án, DB, phân quyền, seed dữ liệu — xem [phase0-setup.md](phase0-setup.md) | Xong |
| Phase 1 | Tính năng cốt lõi (CRM → điều trị → thu ngân → báo cáo) — xem [phase1-core-features.md](phase1-core-features.md) | Phần lớn xong, đang hoàn thiện |
| Phase 2 | **Chuyển giao diện Inertia/Vue → Blade + Alpine + JS thuần** | Đang làm — mới có `layouts/app`, `partials/sidebar`, `partials/topbar`, `components/icon` |
| Phase 3 | Kế toán HKD (TT152), quyết toán kỳ, sổ sách | Đang hoàn thiện |
| Phase 4 | HR: chấm công thiết bị, bảng lương, KPI/chất lượng | Đang hoàn thiện |
| Phase 5 | Tối ưu hiệu năng, index DB, đối soát dữ liệu di trú | Liên tục |

## 5. Ràng buộc xuyên suốt (không thương lượng)

1. **View chỉ là View.** Không business logic, không query, không tính toán nặng trong file View.
2. **Không load lại trang.** Mọi thao tác trên web xử lý 100% bằng JavaScript (fetch/Ajax + DOM),
   luôn `event.preventDefault()`.
3. **Phân bổ file đúng tầng** theo [architecture-workflow.md](../docs/architecture-workflow.md).
4. **DB an toàn**: `.env` local đang trỏ **database production** — mọi lệnh artisan chạm dữ liệu
   phải dry-run trước. Xem [database-safety.md](../docs/database-safety.md).
5. Chỉ commit/deploy khi người dùng yêu cầu rõ ràng.

## 6. Việc còn nợ đã nhận diện

- `resources/views/layouts/app.blade.php` `@include('partials.flash')` nhưng **chưa có** file
  `resources/views/partials/flash.blade.php`.
- Layout Blade nạp `asset('css/app.css')` nhưng `public/css/` đang rỗng (Vite build ra `public/build/`).
- Chưa có `CLAUDE.md` ở gốc dự án.
- Test mỏng: chỉ 14 file trong `tests/`, chủ yếu là test Breeze mặc định.
