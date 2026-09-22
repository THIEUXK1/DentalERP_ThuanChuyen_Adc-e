# Laravel Architecture — Dental ERP

Tra cứu khi cần biết code nằm ở đâu, phụ thuộc nào dùng để làm gì, và những cạm bẫy đã thực sự
cắn trong dự án này. Quy tắc phân bổ file: [architecture-workflow.md](architecture-workflow.md) §2.

## 1. Cây thư mục

```
app/
  Http/Controllers/{Accounting,Admin,Auth,Cashier,Catalog,Clinical,Core,Crm,
                    Dental,Hkd,Hr,Lab,Reports,Schedule}/     ~108 controller
  Http/Requests/                 FormRequest — toàn bộ validate
  Http/Middleware/
  Services/                      nghiệp vụ nhiều bước (+ Dental/, Hkd/, Hr/)
  Models/                        ~95 Eloquent model (+ Concerns/ trait dùng chung)
  Enums/                         ~50 enum backed — trạng thái nghiệp vụ
  Support/                       helper PHP thuần (LegacyExcelValue, RowRangeReadFilter)
  Exports/  Jobs/  Console/Commands/  Policies/
database/migrations/             ~130 migration
resources/
  views/                         lớp UI mới (Blade) — hiện mới có app.blade.php + pdf/
  views/pdf/                     template dompdf
  js/Pages/<Module>/             lớp UI cũ Inertia + Vue 3 (~140 trang)
  js/Components/  js/Layouts/  js/utils/
public/js/                       JS thuần cho lớp Blade (chưa tạo — Phase 2)
routes/web.php                   toàn bộ route ứng dụng  ·  routes/auth.php  Breeze
config/                          cấu hình + menu.php (menu sidebar, lọc quyền ở server)
tests/Feature/  tests/Unit/
```

## 2. Phụ thuộc — mỗi gói một lý do

| Gói | Dùng để làm gì |
|---|---|
| `laravel/framework` ^12, PHP ^8.2 | Nền tảng |
| `inertiajs/inertia-laravel` ^2 + `@inertiajs/vue3` + `vue` ^3.4 | Lớp UI **cũ**, đang bị thay dần bởi Blade + Alpine (Phase 2). Không thêm trang Vue mới |
| `tightenco/ziggy` ^2 | Gọi `route()` từ JS ở lớp Vue. Lớp Blade mới dùng URL do server truyền xuống, không cần Ziggy |
| `spatie/laravel-permission` ^6 | Vai trò & quyền dạng `<module>.<action>`; kiểm ở middleware/Policy, **không** chỉ ẩn nút |
| `spatie/laravel-activitylog` ^4 | Audit log append-only cho thay đổi dữ liệu nghiệp vụ |
| `maatwebsite/excel` ^3 | Xuất báo cáo Excel (`app/Exports/`) và đọc bộ dữ liệu Excel cũ khi đối soát |
| `barryvdh/laravel-dompdf` ^3 | In phiếu thu, phiếu điều trị — template ở `resources/views/pdf/` |
| `laravel/sanctum` ^4 + `laravel/breeze` | Auth phiên đăng nhập nhân viên (không có portal bệnh nhân) |
| `chart.js` ^4 | Biểu đồ dashboard/báo cáo |
| `dayjs` ^1.11 | Format ngày phía client (PHP dùng Carbon) |
| `@headlessui/vue`, `@heroicons/vue` | Chỉ phục vụ lớp Vue cũ. Lớp Blade dùng `app/Support/Icons` + `<x-icon>` |
| `tailwindcss` ^3 + `vite` ^7 + `laravel-vite-plugin` ^2 | CSS + build. **Tailwind giữ ở v3**, không nâng v4 dù `@tailwindcss/vite` có trong devDeps |
| `laravel/pint` | Format PHP — `php vendor/bin/pint --quiet` |
| `laravel/pail` | Xem log realtime khi cần (`php artisan pail`) |

Thêm dependency mới = quyết định kiến trúc → ADR vào
[`../plans/00-context-memory.md`](../plans/00-context-memory.md) trước.

## 3. Quy ước bắt buộc

- **Route**: `routes/web.php` gom theo module bằng `Route::prefix('<module>')->name('<module>.')`.
  Prefix đang dùng: `accounting`, `admin`, `cashier`, `catalog`, `clinical`, `core`, `crm`,
  `dental`, `hkd`, `hr`, `inventory`, `lab`, `reports`, `schedule`.
  Route name luôn `module.resource.action`. Xem nhanh:
  `php artisan route:list --path=<prefix> --columns=method,uri,name`.
- **Quyền**: mỗi route nghiệp vụ gắn middleware permission `<module>.<action>`; tính năng mới
  phải khai báo permission tương ứng.
- **Enum**: trạng thái nghiệp vụ luôn là backed enum trong `app/Enums/`, không dùng chuỗi trần.
- **PriceResolver**: mọi chỗ cần giá dịch vụ phải đi qua `app/Services/PriceResolver.php`,
  không tự tra bảng giá.
- **Soft delete + duyệt xoá**: `deleted_at` + bảng `pending_deletions`. Không `forceDelete`
  dữ liệu nghiệp vụ.
- **Vite**: entry duy nhất `resources/js/app.js`, `refresh: true`, dev server `127.0.0.1:5173`.
  Lớp Blade mới nạp asset qua `@vite` (không tự trỏ `asset('css/app.css')` — xem cạm bẫy P2).
- **Test**: `phpunit.xml` đã ép `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` → chạy test
  **không** đụng DB production. Đừng sửa hai dòng này.

## 4. Cạm bẫy đã thực sự cắn

| # | Cạm bẫy | Cách tránh |
|---|---|---|
| P1 | `.env` local trỏ thẳng **DB production** → mọi `php artisan` là tác động dữ liệu thật của phòng khám | Kiểm `DB_HOST`/`DB_DATABASE` trước mọi lệnh ghi; dry-run (`--pretend`) rồi xin xác nhận. Xem [database-safety.md](database-safety.md) |
| P2 | Layout Blade từng trỏ `asset('css/app.css')` trong khi Vite build ra `public/build/` → trang Blade không có CSS | Dùng `@vite(['resources/css/app.css', 'resources/js/app.js'])`, không hardcode đường dẫn asset |
| P3 | `layouts/app.blade.php` `@include('partials.flash')` khi file chưa tồn tại → `View not found` ngay lần render đầu | Tạo đủ partial trước khi include; grep `@include`/`<x-` khi thêm layout mới |
| P4 | Ngày trong bộ Excel cũ bị Excel đọc kiểu Mỹ; 49.348/136.523 dòng đã bị đảo ngày↔tháng trên production | Mọi lần bù dữ liệu phải dùng lại `App\Support\LegacyExcelValue::unswapLegacyDate` (ngày ≤ 12 thì đảo). Dùng quy tắc khác sẽ sinh bản ghi trùng lệch ngày |
| P5 | `clinic_records` (Excel cũ) kết thúc **10/05/2026**; sau mốc đó ERP chạy thật (`patient_payments`) | Không import thêm Excel sau mốc này — sẽ chồng lấn dữ liệu live |
| P6 | Dời lịch hẹn tạo bản ghi **mới** và giữ bản cũ ở trạng thái `rescheduled` (`rescheduled_from_id`) | Mọi báo cáo đếm lịch hẹn phải loại trạng thái `rescheduled`, nếu không sẽ đếm trùng |
| P7 | Hai lớp UI (Vue cũ + Blade mới) song song, chưa có tiêu chí "trang nào đã chuyển xong" | Trước khi sửa một màn hình: xác định route đó đang render Inertia hay Blade, đừng sửa nhầm lớp |
| P8 | Vite mặc định xoá terminal mỗi lần rebuild → mất log lỗi của phiên | Luôn `npm run dev -- --clearScreen false` ([`../rules/core.md`](../rules/core.md) §8) |
| P9 | Danh sách lớn không eager load → N+1 trên bảng hàng trăm nghìn dòng (`patient_payments`, `clinic_records`) | `with()` mọi quan hệ dùng trong vòng lặp; `select()` cột cần; phân trang bắt buộc |

## 5. Lệnh hay dùng

```bash
php artisan serve                          # + npm run dev -- --clearScreen false (nền)
php artisan route:list --path=schedule --columns=method,uri,name
php artisan migrate:status | tail -20
php artisan migrate --pretend | tail -40   # dry-run, luôn chạy trước
php artisan test --filter=<Tên> | tail -30
php vendor/bin/pint --quiet
npm run build --silent | tail -20
tail -50 storage/logs/laravel.log
```

`composer dev` chạy gộp serve + queue + pail + vite bằng `concurrently` — tiện nhưng output
trộn lẫn; khi cần đọc log thì chạy tách từng lệnh ở nền.
