# Context Memory — việc đang dở

Đọc đầu mỗi task. ≤ 3 KB. Quá 5 quyết định → đẩy dòng cũ nhất xuống [decision-log.md](decision-log.md).

## Đang làm

- Phase 2: chuyển UI Inertia + Vue → Blade + Alpine + JS thuần. Nhánh `main`.
- Đang dở: khung Blade chưa có — `resources/views/` chỉ có `app.blade.php` + `pdf/`; thiếu
  `layouts/` `partials/` `components/` `public/js/` `config/menu.php` `App\Support\{Asset,Icons,Menu}`.
- Tiếp theo: dựng layout + partials + `@vite`, rồi chuyển từng trang theo thứ tự route,
  bắt đầu Dashboard và Đăng ký khám.
- Vừa xong (2026-09-22): dời lịch hẹn hàng loạt (`rescheduled_from_id`, `bulk-move`);
  nút "＋ Lịch hẹn"/"Đăng ký khám" trên `/patients`. Migration đã chạy, backup ở `storage/app/backups/`.

## Blockers

- B2 Khung Blade chưa có → dựng trước khi chuyển trang; tránh P2/P3 ở `docs/laravel-architecture.md` §4.
- B3 Vue + Blade song song, chưa có tiêu chí "trang đã chuyển xong" → dễ sửa nhầm lớp cũ.
- B4 Test mỏng (~14 file, chủ yếu Breeze) → refactor UI thiếu lưới an toàn.

## 5 quyết định gần nhất

- 2026-10-13 — `core.md` cắt về ≤ 6 KB, bỏ phần trùng global; settings thêm deny `Read` rác/secret —
  phần tự nạp ≤ 10 KB — tài liệu chi tiết chỉ đọc theo `core.md` §9.
- 2026-10-08 — Quyền tách `settings.json` (dev) + `settings.local.json` (SSH key `dental_erp_backup`) —
  `.env` local = DB production — ghi production vẫn hỏi từng lần ([database-safety.md](../docs/database-safety.md)).
- 2026-09-22 — `rules/` chỉ còn `core.md`, 4 file cũ sang `docs/` — `rules/` tự nạp mỗi lượt —
  tra cứu theo bảng lazy-load.
- 2026-09-22 — Dời lịch sang ngày khác = tạo lịch mới + giữ lịch cũ `rescheduled` (`rescheduled_from_id`);
  `reschedule()` chỉ đổi giờ cùng ngày — lễ tân cần thấy ngày lỡ hẹn — báo cáo đếm lịch hẹn **phải loại** `rescheduled`.
- 2026-08-28 — Dev luôn `npm run dev -- --clearScreen false` — HMR — thiếu cờ thì mất log lỗi.

## Ghi chú phiên sau

- (trống)
