# Coding Standards

Ngắn gọn, tra nhanh. Chuẩn của repo thắng sở thích cá nhân.

## 1. Naming

| Đối tượng | Quy ước | Ví dụ |
|---|---|---|
| Class PHP | PascalCase | `TreatmentPlanService` |
| Method / biến PHP | camelCase | `calculateKpi()`, `$planItems` |
| Hằng số | UPPER_SNAKE | `MAX_ITEMS` |
| Bảng DB | snake_case số nhiều | `treatment_plan_items` |
| Cột DB | snake_case | `start_date`, `doctor_id` |
| Khoá ngoại | `<bảng_số_ít>_id` | `patient_id` |
| Enum | PascalCase + case UPPER hoặc PascalCase | `app/Enums/InvoiceStatus.php` |
| Route name | `module.resource.action` | `clinical.treatment-plans.index` |
| File Blade | kebab-case | `patient-receipt.blade.php` |
| Component Blade | kebab-case, gọi `<x-…>` | `<x-icon name="home" />` |
| File JS | kebab-case hoặc camelCase theo thư mục hiện có | `exportExcel.js`, `usePatientDetail.js` |
| Migration | timestamp Laravel + động từ | `add_doctor_id_to_patient_payments_table` |
| Permission | `<module>.<action>` | `patients.view`, `reports.financial` |
| Nhánh git | `feat/…`, `fix/…` | `feat/blade-dashboard` |

## 2. PHP

- PSR-12, format bằng `php vendor/bin/pint` trước khi commit.
- Khai báo kiểu đầy đủ: tham số, kiểu trả về, property.
- Controller **mỏng**: validate (FormRequest) → gọi Service → trả response. Tối đa ~30 dòng/action.
- Business logic ở Service; Service không đụng `request()`, `session()`, `redirect()`.
- Dùng Enum thay chuỗi/magic number cho trạng thái.
- Tiền: dùng số nguyên/decimal nhất quán, **không** float cho phép tính tiền.
- Ngày giờ: Carbon; lưu UTC theo cấu hình app, format ở tầng hiển thị.
- Comment bằng tiếng Việt, giải thích **tại sao**, không diễn giải lại code. Giữ đúng mật độ
  comment như code xung quanh.

## 3. Truy vấn DB

- Luôn eager load quan hệ dùng trong vòng lặp (`with()`) — **N+1 là lỗi MAJOR**.
- Chỉ `select()` cột cần cho màn hình danh sách lớn.
- Bắt buộc phân trang cho mọi danh sách có thể lớn.
- Không nối chuỗi input người dùng vào raw SQL; cột động (`ORDER BY`) phải whitelist.
- Thao tác nhiều bảng → bọc `DB::transaction()`.

## 4. Design Patterns dùng trong repo

| Pattern | Nơi dùng |
|---|---|
| Service layer | `app/Services/*` — mọi quy trình nghiệp vụ nhiều bước |
| Form Request | `app/Http/Requests/*` — toàn bộ validate |
| Enum backed | `app/Enums/*` — trạng thái nghiệp vụ |
| Trait / Concerns | `app/Models/Concerns/*` — hành vi Model dùng chung |
| Resolver | `PriceResolver` — quy tắc chọn giá |
| Soft delete + duyệt xoá | `deleted_at` + `pending_deletions` |
| Append-only audit | `*_audit_logs`, `spatie/activitylog` |
| Config-driven UI | `config/menu.php` + `App\Support\Menu` |

## 5. JavaScript — bắt buộc tách file riêng

- **Mỗi màn hình có tương tác = một file JS độc lập** trong `public/js/` (đặt tên theo màn
  hình, ví dụ `public/js/schedule-registrations.js`), nạp bằng `@push('scripts')`.
  Không viết khối `<script>` dài trong Blade; chỉ được để lại vài dòng khởi tạo/truyền tham số.
- File JS xử lý **100% tương tác client, không reload trang**:
  - `fetch(url, {headers: {'X-CSRF-TOKEN': …, 'Accept': 'application/json'}})`.
  - `event.preventDefault()` trên mọi submit/click nghiệp vụ.
  - Cập nhật DOM **cục bộ** đúng phần đã đổi; không `location.reload()`.
- Alpine.js dùng cho trạng thái UI nhỏ (đóng/mở, tab, dropdown); logic dài đưa vào file JS.
- `const`/`let`, không `var`. Không biến toàn cục ngoài một namespace duy nhất của trang.
- Gỡ event listener khi huỷ phần tử; debounce ô tìm kiếm; chặn double-submit.
- Không nhúng dữ liệu nhạy cảm hay logic phân quyền vào JS — quyền luôn kiểm ở server.

## 6. Blade

- Escape mặc định `{{ }}`. `{!! !!}` chỉ dùng cho HTML do hệ thống sinh, đã kiểm soát.
- Component tái dùng đặt ở `resources/views/components/`.
- Không logic nghiệp vụ trong Blade (xem [architecture-workflow.md](architecture-workflow.md) §3).

## 7. Test

- Feature test cho luồng HTTP, Unit test cho Service tính toán (tiền, lương, KPI).
- Ưu tiên test **hành vi**, không test implementation.
- Test không được chạm DB production (xem [database-safety.md](database-safety.md)).

## 8. Terminal output, hot reload, zero-fluff

Bảng lệnh thu gọn output, quy tắc hot reload (`npm run dev -- --clearScreen false`) và quy tắc
zero-fluff nằm ở [`../rules/core.md`](../rules/core.md) §7–§8. Không nhắc lại ở đây.
