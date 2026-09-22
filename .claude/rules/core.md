# Core Rules — Dental ERP

File này **tự nạp mỗi phiên**. Giữ ngắn. Chi tiết nằm ở `.claude/docs/` — xem bảng §9.

## 1. Dự án

ERP cho một phòng khám nha khoa (nội bộ, không phải SaaS, không có portal bệnh nhân):
CRM · đăng ký khám & lịch hẹn · lâm sàng · kế hoạch điều trị · thu ngân/công nợ · kho & labo ·
nhân sự (chấm công, lương, KPI) · kế toán hộ kinh doanh TT152 · báo cáo.

**Stack đã chốt — không tự ý đổi:** PHP 8.2 · Laravel 12 · MySQL · `spatie/laravel-permission`
+ `activitylog` · Tailwind 3 + Vite 7 · lớp UI cũ Inertia 2 + Vue 3 · **lớp UI mới Blade +
Alpine + JS thuần** (Phase 2 đang chuyển dần). Thêm dependency hay đổi stack → ADR trong
[Decision Log](../plans/00-context-memory.md).

## 2. Phân lớp

```
routes/web.php → middleware (auth, permission) → Controller (mỏng)
  → FormRequest (validate) → Service (nghiệp vụ) → Model/Eloquent → View Blade (render thuần)
                                                                  ⇄ public/js/*.js (fetch, DOM)
```

Controller không chứa công thức nghiệp vụ · Service không biết gì về HTTP (`request()`,
`session()`, `redirect()`) · Model không chứa quy trình nhiều bước.

## 3. Phân bổ file (rút gọn — bảng đầy đủ ở `docs/architecture-workflow.md` §2)

| Loại code | Vị trí duy nhất |
|---|---|
| UI / markup | `resources/views/` (`layouts/`, `partials/`, `components/`, `pdf/`) |
| JS tương tác | `public/js/*.js` (lớp Blade) · `resources/js/` (lớp Vue cũ) |
| Điều phối request | `app/Http/Controllers/<Module>/` |
| Validate | `app/Http/Requests/` |
| Nghiệp vụ | `app/Services/` (`Dental/`, `Hkd/`, `Hr/`) |
| Query, quan hệ, scope | `app/Models/` (+ `Concerns/`) |
| Trạng thái | `app/Enums/` |
| Helper thuần | `app/Support/` (PHP) · `resources/js/utils/` (JS) |
| Excel · Job · Artisan · Config tĩnh | `app/Exports/` · `app/Jobs/` · `app/Console/Commands/` · `config/` |

Trước khi tạo file mới: tìm xem đã có Service/Support/component tương đương chưa — **tái sử dụng
thắng viết mới**.

## 4. CẤM tuyệt đối

- Tạo file ngoài cấu trúc §3 (không thư mục "tạm"/"misc"/"new" ở gốc).
- Trộn UI với nghiệp vụ trong một file · query Eloquent trong View · echo HTML trong Controller/Service.
- Sửa `vendor/`, `node_modules/`, `public/build/`.
- **Chạy lệnh ghi lên DB khi chưa dry-run + chưa được xác nhận** — `.env` local trỏ **DB
  PRODUCTION**. Cấm `migrate:fresh|reset|refresh|rollback`, `db:wipe`, `db:seed`, `DROP`,
  `TRUNCATE`, `DELETE`/`UPDATE` không WHERE. Chi tiết: `docs/database-safety.md`.
- Commit / push / deploy khi người dùng không yêu cầu. Không `--no-verify`, không `push --force`
  lên `main`, không commit `.env` hay file sinh ra.
- Hardcode credential (DB, API key, token, mail). Secret chỉ ở `.env` (đã gitignore);
  `.env.example` chỉ giữ tên key + giá trị giả. Không phơi key backend ra client/JS.
- Kiểm quyền chỉ ở UI. Mọi tính năng mới phải có permission `<module>.<action>` và
  **kiểm ở server**, không chỉ ẩn nút.

## 5. View & tương tác web — không thương lượng

**View là Dumb UI.** Blade chỉ nhận dữ liệu đã dọn sẵn, lặp render, gọi helper hiển thị thuần
(format tiền/ngày/icon), `@include`/`<x-…>`. Không `Model::`, `DB::`, `->get()`, `->sum()`,
không gọi Service, không gọi HTTP, không vòng lặp tính toán/lọc/gộp. Controller/Service dọn dữ
liệu thành đúng hình dạng View cần rồi truyền xuống.

**Web không bao giờ reload trang.** Mọi thao tác (tạo/sửa/xoá/lọc/phân trang/đổi trạng thái/
tab/modal) chạy 100% bằng JS:

- `fetch(url, { headers: { 'X-CSRF-TOKEN': …, 'Accept': 'application/json' } })` → JSON →
  cập nhật **DOM cục bộ** đúng phần đã đổi.
- Luôn `event.preventDefault()` trên mọi `submit` và mọi `click` thẻ `<a>` có hành vi nghiệp vụ.
- Cấm `location.reload()`, cấm `window.location = …` cho thao tác dữ liệu.
- Event delegation trên `document` cho phần tử sinh động; gỡ listener khi huỷ phần tử.
- Controller phục vụ các thao tác này trả `response()->json([...])`, không redirect.
- Lỗi hiển thị tại chỗ (inline/toast), không nhảy trang lỗi. Khoá nút + spinner cục bộ chống
  double-submit. Nếu `fetch` hỏng hẳn → fallback điều hướng thật, không để người dùng treo.

Ngoại lệ được reload: đăng nhập/đăng xuất và tải file (PDF/Excel).

## 6. Code style (tóm tắt — đầy đủ ở `docs/coding-standards.md`)

- PSR-12, `php vendor/bin/pint --quiet` trước khi commit. Khai báo kiểu đầy đủ.
- Controller ≤ ~30 dòng/action: FormRequest → Service → response.
- Enum thay magic string. Tiền dùng decimal/integer, **không float**. Ngày giờ dùng Carbon.
- Naming: class `PascalCase` · method/biến `camelCase` · bảng `snake_case` số nhiều · FK
  `<bảng_số_ít>_id` · route `module.resource.action` · Blade & component kebab-case ·
  permission `<module>.<action>` · nhánh `feat/…` `fix/…` `refactor/…` `chore/…`.
- DB: `with()` cho quan hệ dùng trong vòng lặp (**N+1 là lỗi MAJOR**) · `select()` cột cần ·
  phân trang bắt buộc · không nối chuỗi input vào raw SQL, cột động phải whitelist ·
  nhiều bảng → `DB::transaction()`.
- Xoá dữ liệu nghiệp vụ = soft delete (`deleted_at`) + `pending_deletions`; audit log append-only.
- JS: mỗi màn hình một file trong `public/js/`, nạp bằng `@push('scripts')`. Không khối
  `<script>` dài trong Blade. `const`/`let`, không `var`, không biến toàn cục ngoài một
  namespace của trang. Alpine chỉ giữ trạng thái UI nhỏ.
- Comment tiếng Việt, giải thích **tại sao**, giữ đúng mật độ comment của code xung quanh.
- Commit: Conventional Commits + scope module, tiếng Việt — `feat(schedule): …`.

## 7. Zero-fluff & output clamping

- Trả lời trực diện: không chào hỏi, không mở bài, không tóm tắt lại yêu cầu, không khen,
  không kết luận thừa. Không viết doc/changelog không được yêu cầu.
- Chỉ xuất **đoạn code thay đổi**; không in lại file/phần không đổi. Tham chiếu bằng
  `đường-dẫn:số-dòng`. Không đọc lại file vừa sửa để "xác minh" nếu lệnh sửa đã báo thành công.
- Mọi lệnh phải thu gọn output — dự kiến > ~50 dòng thì bắt buộc `head`/`tail`/`grep`/`--quiet`:

| Việc | Lệnh |
|---|---|
| Test | `php artisan test --stop-on-failure \| tail -30` · `--filter=<Tên>` |
| Format | `php vendor/bin/pint --quiet` · kiểm tra: `--test \| tail -20` |
| Build | `npm run build --silent \| tail -20` |
| Cài gói | `composer install -q` · `npm ci --silent` |
| Git | `git status --short` · `git log --oneline -10` · `git diff --stat` trước khi diff file |
| Lọc lỗi | `<lệnh> 2>&1 \| grep -E "FAIL\|ERROR\|Exception" \| head -30` |
| Log | `tail -50 storage/logs/laravel.log` (không `cat`) |
| Đọc file dài | `sed -n '1,80p' <file>` |
| `grep`/`find`/`ls`/`cat` | luôn `\| head -n 20` |
| Route | `php artisan route:list --path=<prefix> --columns=method,uri,name` |

Chỉ chạy lại verbose khi thất bại, và chỉ đúng phần lỗi.

## 8. Hot reload (bắt buộc khi dev)

`php artisan serve` + `npm run dev -- --clearScreen false`, chạy **nền**.
Cờ `--clearScreen false` là bắt buộc: Vite mặc định xoá terminal mỗi lần rebuild, nuốt mất log
lỗi của lần nạp trước. Đọc log bằng `tail`, không đổ toàn bộ output.
Sửa giao diện/cấu hình mà chưa thấy đổi → **chủ động restart ngay**, không hỏi.
Không mở Laragon GUI. Không kéo build step mới vào chỉ để có hot reload.

Stack khác (nếu chạm tới): Vite `--clearScreen false --logLevel warn` · Node `node --watch`/
`nodemon --quiet` · Python `uvicorn --reload --log-level warning` · Go `air` với
`clear_on_rebuild = false` · Rust `cargo watch -q -c false`. Quy tắc chung: **mọi watcher phải
tắt xoá màn hình**.

## 9. Lazy-load — task nào đọc file nào

| Task đang làm | Mở file |
|---|---|
| Bắt đầu phiên / không rõ đang ở đâu | `.claude/plans/00-context-memory.md` |
| Tạo file mới, không chắc đặt ở đâu · git flow · quy trình một thay đổi | `.claude/docs/architecture-workflow.md` |
| Viết code PHP/JS/Blade, naming, design pattern của repo, xử lý lỗi | `.claude/docs/coding-standards.md` |
| Cây thư mục, dependency, route, cạm bẫy đã cắn, lệnh hay dùng | `.claude/docs/laravel-architecture.md` |
| Migration, schema, backfill, index, thao tác ghi dữ liệu | `.claude/docs/database-safety.md` |
| "Có nên làm tính năng này không", in/out of scope | `.claude/docs/project-scope.md` |
| Tra quyết định cũ | `.claude/plans/decision-log.md` |
| Lộ trình / phase | `.claude/plans/00-master-plan.md`, `phase*.md` |

## 10. Subagent

| Agent | Khi nào gọi |
|---|---|
| `system-architect` | Thẩm định thiết kế, đề xuất đổi kiến trúc/stack, chọn giữa nhiều cách làm |
| `database-auditor` | Review migration/schema/backfill trước khi chạy; soát soft-delete, idempotency |
| `code-reviewer` | Review chất lượng & bảo mật một thay đổi trước khi commit |

Cả ba đều **chỉ đọc** (database-auditor có thêm `Edit` để soạn script đề xuất) — không tự chạy
lệnh ghi dữ liệu, không tự commit.
