# Core — Dental ERP

Tự nạp mỗi phiên. Quy tắc chung (zero-fluff, clamping, khi bị chặn) ở `~/.claude/CLAUDE.md`.
Chi tiết: `.claude/docs/` theo §9.

## 1. Dự án

- ERP nội bộ 1 phòng khám nha (không SaaS, không portal BN): CRM · đăng ký khám/lịch hẹn ·
  lâm sàng · kế hoạch điều trị · thu ngân/công nợ · kho/labo · nhân sự · kế toán HKD TT152 · báo cáo.
- Stack chốt: PHP 8.2 · Laravel 12 · PostgreSQL 16 · spatie permission + activitylog ·
  Tailwind 3 + Vite 7 · UI cũ Inertia 2 + Vue 3 · **UI mới Blade + Alpine + JS thuần** (Phase 2).
- Thêm dependency/đổi stack → ADR ở `plans/decision-log.md`.

## 2. Phân lớp

`routes/web.php` → middleware (auth, permission) → Controller mỏng → FormRequest → Service →
Model → Blade ⇄ `public/js/*.js`.
- Controller không công thức nghiệp vụ · Service không `request()`/`session()`/`redirect()` ·
  Model không quy trình nhiều bước.

## 3. Phân bổ file (đầy đủ: `docs/architecture-workflow.md` §2)

| Loại | Vị trí |
|---|---|
| UI | `resources/views/` (`layouts/` `partials/` `components/` `pdf/`) |
| JS | `public/js/*.js` (Blade) · `resources/js/` (Vue cũ) |
| Controller | `app/Http/Controllers/<Module>/` |
| Validate | `app/Http/Requests/` |
| Nghiệp vụ | `app/Services/` (`Dental/` `Hkd/` `Hr/`) |
| Query/quan hệ/scope | `app/Models/` (+ `Concerns/`) |
| Trạng thái | `app/Enums/` |
| Helper | `app/Support/` · `resources/js/utils/` |
| Excel/Job/Artisan/Config | `app/Exports/` `app/Jobs/` `app/Console/Commands/` `config/` |

Tìm Service/Support/component có sẵn trước khi tạo mới.

## 4. CẤM

- File ngoài §3; thư mục tạm/misc/new ở gốc.
- Trộn UI + nghiệp vụ · query trong View · echo HTML trong Controller/Service.
- Sửa `vendor/` `node_modules/` `public/build/`.
- **`.env` local = DB PRODUCTION.** Lệnh ghi DB phải dry-run + xác nhận. Cấm
  `migrate:fresh|reset|refresh|rollback`, `db:wipe`, `db:seed`, `DROP`, `TRUNCATE`,
  `DELETE`/`UPDATE` không WHERE. → `docs/database-safety.md`.
- Commit/push/deploy khi chưa được yêu cầu · `--no-verify` · `push --force` lên `main` ·
  commit `.env`/file sinh ra.
- Hardcode credential; secret chỉ ở `.env`, `.env.example` giữ tên key + giá trị giả; không lộ key ra JS.
- Kiểm quyền chỉ ở UI. Tính năng mới: permission `<module>.<action>` kiểm ở server.

## 5. View & tương tác web

- **Dumb UI:** Blade chỉ render dữ liệu dọn sẵn + helper hiển thị + `@include`/`<x-…>`.
  Không `Model::` `DB::` `->get()` `->sum()`, không gọi Service/HTTP, không lọc/gộp/tính.
- **Không reload trang:** `fetch` (header `X-CSRF-TOKEN`, `Accept: application/json`) → JSON →
  cập nhật DOM cục bộ. `preventDefault()` mọi submit/click `<a>` nghiệp vụ.
- Cấm `location.reload()` và `window.location = …` cho thao tác dữ liệu.
- Event delegation trên `document`; gỡ listener khi huỷ phần tử.
- Controller trả `response()->json()`, không redirect. Lỗi inline/toast. Khoá nút + spinner.
  `fetch` hỏng hẳn → fallback điều hướng thật.
- Ngoại lệ được reload: login/logout, tải PDF/Excel.

## 6. Code style (đầy đủ: `docs/coding-standards.md`)

- PSR-12 + Pint. Kiểu đầy đủ. Action Controller ≤ ~30 dòng.
- Enum thay magic string · tiền decimal/integer, **không float** · ngày giờ Carbon.
- Naming: class `PascalCase` · method/biến `camelCase` · bảng `snake_case` số nhiều · FK
  `<bảng_số_ít>_id` · route `module.resource.action` · Blade kebab-case · nhánh `feat|fix|refactor|chore/…`.
- DB: `with()` trong vòng lặp (**N+1 = MAJOR**) · `select()` cột cần · phân trang bắt buộc ·
  không nối input vào raw SQL, cột động whitelist · nhiều bảng → `DB::transaction()`.
- Xoá nghiệp vụ = soft delete + `pending_deletions`; audit log append-only.
- JS: mỗi màn hình 1 file `public/js/`, nạp `@push('scripts')`; không `<script>` dài trong Blade;
  `const`/`let`, một namespace/trang. Alpine chỉ giữ state UI nhỏ.
- Comment tiếng Việt, giải thích tại sao. Commit Conventional + scope, tiếng Việt: `feat(schedule): …`.

## 7. Lệnh stack (đã kẹp output)

| Việc | Lệnh |
|---|---|
| Test | `php artisan test --stop-on-failure \| tail -30` · `--filter=<Tên>` |
| Format | `php vendor/bin/pint --quiet` · kiểm: `--test \| tail -20` |
| Build | `npm run build --silent \| tail -20` |
| Cài gói | `composer install -q` · `npm ci --silent` |
| Route | `php artisan route:list --path=<prefix> --columns=method,uri,name` |
| Log | `tail -50 storage/logs/laravel.log` |

## 8. Hot reload

- Nền: `php artisan serve > storage/logs/serve-debug.log 2>&1` +
  `npm run dev -- --clearScreen false > storage/logs/vite-debug.log 2>&1`.
- `--clearScreen false` bắt buộc (Vite xoá terminal, nuốt log lỗi). Không mở Laragon GUI.

## 9. Task → đọc file nào

| Task | File |
|---|---|
| Đầu phiên / không rõ đang ở đâu | `.claude/plans/00-context-memory.md` |
| Tạo file mới · git flow · quy trình thay đổi | `.claude/docs/architecture-workflow.md` |
| Viết PHP/JS/Blade, naming, pattern, xử lý lỗi | `.claude/docs/coding-standards.md` |
| Cây thư mục, dependency, route, cạm bẫy, deploy | `.claude/docs/laravel-architecture.md` |
| Migration, schema, backfill, ghi dữ liệu | `.claude/docs/database-safety.md` |
| In/out of scope | `.claude/docs/project-scope.md` |
| Quyết định cũ · lộ trình | `.claude/plans/decision-log.md` · `00-master-plan.md` · `phase*.md` |

Subagent (chỉ đọc, gọi khi người dùng yêu cầu): `system-architect` (thiết kế/đổi stack) ·
`database-auditor` (migration/backfill) · `code-reviewer` (trước commit).
