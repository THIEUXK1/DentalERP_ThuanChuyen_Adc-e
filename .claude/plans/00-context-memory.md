# Context Memory — trạng thái làm việc hiện tại

> Cập nhật file này **mỗi khi** đổi task, gặp blocker, hoặc chốt một quyết định kỹ thuật.
> Đây là bộ nhớ bối cảnh giữa các phiên làm việc.

## Ràng buộc cứng

Quy tắc đầy đủ ở [`../rules/core.md`](../rules/core.md) (tự nạp mỗi phiên). Bốn điều không được
quên giữa các phiên:

1. View là **Dumb UI** — không query, không gọi Service, không tính nghiệp vụ trong Blade.
2. Thao tác web **không bao giờ reload trang** — fetch + JSON + cập nhật DOM cục bộ,
   luôn `event.preventDefault()`.
3. `.env` local trỏ **DB production** — không chạy lệnh ghi khi chưa dry-run + xin xác nhận.
4. Không commit / push / deploy nếu người dùng không yêu cầu.

## Sprint / Task đang làm

| Mục | Nội dung |
|---|---|
| Giai đoạn | Phase 2 — chuyển UI từ Inertia + Vue sang Blade + Alpine + JS thuần |
| Nhánh | `main` |
| Đang dở | Lớp Blade **chưa khởi động lại**: `resources/views/` mới có `app.blade.php` + `pdf/`; chưa có `layouts/`, `partials/`, `components/`, `public/js/`, `config/menu.php`, `App\Support\{Asset,Icons,Menu}`. (Ghi chú phiên trước nói các file này đã tồn tại dạng untracked — **không đúng với working tree hiện tại**) |
| Vừa xong | (2026-09-22) Dời lịch hẹn hàng loạt: cột `appointments.rescheduled_from_id`, `AppointmentService::moveToNewDate()`, endpoint `schedule.appointments.bulk-move`, UI chọn nhiều + nhãn "Đã dời sang / Dời từ" trong `Schedule/Appointments/Index.vue`. Migration **đã chạy** (backup `storage/app/backups/appointments_20260922_before_rescheduled_from_id.csv`, 4992 dòng trước/sau). Kèm nút "＋ Lịch hẹn" và "🗓 Đăng ký khám" mở form ngay trên `/patients` (`QuickRegisterModal.vue`, `patients.quick-register` trả JSON khi `expectsJson`) + endpoint `schedule.appointments.options` |
| Vừa xong trước đó | (2026-08-27) Đối soát `clinic_records` với bộ Excel `DuLieuHeThong`: bù 559 dòng thiếu, xoá cứng 64.823 dòng tàn dư import lỗi (đã sao lưu CSV) |
| Tiếp theo | Dựng lại khung Blade (layout + partials + `@vite`), rồi chuyển từng trang Vue → Blade theo thứ tự route trong `routes/web.php`, bắt đầu từ Dashboard và Đăng ký khám |

## Blockers

| # | Blocker | Ảnh hưởng |
|---|---|---|
| B2 | Khung Blade Phase 2 không còn trong working tree (xem "Đang dở") | Phải dựng lại trước khi chuyển trang; tránh lặp lỗi cũ P2/P3 trong `../docs/laravel-architecture.md` §4 |
| B3 | Hai lớp UI (Vue + Blade) song song, chưa có tiêu chí "trang nào đã chuyển xong" | Rủi ro sửa nhầm lớp cũ |
| B4 | Test coverage rất mỏng (~14 file, chủ yếu Breeze mặc định) | Refactor UI không có lưới an toàn |

## Decision Log — 5 quyết định gần nhất

Lịch sử cũ hơn: [decision-log.md](decision-log.md).

| Ngày | Quyết định | Lý do | Hệ quả |
|---|---|---|---|
| 2026-09-22 | Gom `.claude/rules/` về **một** file `core.md`; 4 file cũ chuyển sang `.claude/docs/` | `rules/` bị tự nạp mỗi phiên — 23 KB tài liệu tra cứu ngốn ngữ cảnh vô ích | Phần tự nạp còn ~9 KB; tài liệu chi tiết đọc theo bảng lazy-load ở `core.md` §9 |
| 2026-09-22 | Dời lịch sang ngày khác = **tạo lịch hẹn mới + giữ lịch cũ** ở trạng thái `rescheduled`, liên kết bằng `rescheduled_from_id`; `reschedule()` (sửa tại chỗ) chỉ còn dùng khi đổi giờ trong cùng ngày | Lễ tân cần thấy bệnh nhân đã lỡ hẹn ngày nào; sửa tại chỗ làm mất dấu lịch cũ | Một bệnh nhân lỡ hẹn nhiều lần sẽ sinh chuỗi lịch hẹn → báo cáo đếm lịch hẹn **phải loại** trạng thái `rescheduled` |
| 2026-08-28 | Dev luôn chạy hot reload: `npm run dev -- --clearScreen false` (Vite HMR + `refresh: true`) | Sửa file là trình duyệt tự cập nhật | Cờ `--clearScreen false` là bắt buộc — thiếu nó Vite xoá terminal và nuốt log lỗi giữa phiên |
| 2026-08-27 | Mốc dữ liệu: `clinic_records` (Excel cũ) kết thúc **10/05/2026**, từ đó ERP chạy thật (`patient_payments` live) | Bàn giao sạch, không chồng lấn | Không import thêm Excel sau mốc này |
| 2026-08-27 | Quy tắc phục hồi ngày Excel bị đọc kiểu Mỹ: **ngày ≤ 12 thì đảo ngày↔tháng** (`App\Support\LegacyExcelValue::unswapLegacyDate`) | Khớp đúng dữ liệu production (49.348/136.523 dòng đã đảo) | Mọi lần bù dữ liệu sau phải dùng cùng quy tắc, nếu không sẽ sinh bản ghi trùng lệch ngày |

## Ghi chú cho phiên sau

- Chạy dev: `php artisan serve` + `npm run dev -- --clearScreen false` (nền, không mở Laragon GUI).
- Format PHP: `php vendor/bin/pint --quiet`.
- Deploy: GitHub repo + server SSH `/var/www/DentalERP` — **chỉ khi được yêu cầu**.
- Thay đổi chưa commit tính đến 2026-09-22: `AppointmentController`, `Appointment`,
  `AppointmentService`, `Schedule/Appointments/Index.vue`, `routes/web.php`,
  migration `rescheduled_from_id`, `BackfillLegacyNotes`, `tests/Feature/AppointmentMoveTest.php`,
  cùng toàn bộ `.claude/`.
