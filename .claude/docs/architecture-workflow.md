# Architecture & Workflow

## 1. Kiến trúc chung

```
Request → routes/web.php → Middleware (auth, permission)
        → Controller (mỏng: nhận input, uỷ quyền, trả response)
        → FormRequest (validate)  → Service (business logic)
        → Model / Eloquent (truy vấn dữ liệu)
        → View Blade (render thuần)  ⇄  JS thuần (fetch, cập nhật DOM)
```

Controller **không** chứa công thức nghiệp vụ. Service **không** biết gì về HTTP.
Model **không** chứa quy trình nghiệp vụ nhiều bước.

## 2. QUY TẮC PHÂN BỔ FILE — bắt buộc

| Loại code | Vị trí duy nhất được phép |
|---|---|
| UI / markup / template | `resources/views/` (`layouts/`, `partials/`, `components/`, `pdf/`) |
| Component UI tái dùng | `resources/views/components/` |
| JavaScript tương tác | `public/js/` (lớp Blade) hoặc `resources/js/` (lớp Vue cũ) — **file .js riêng** |
| Điều phối request | `app/Http/Controllers/<Module>/` |
| Validate input | `app/Http/Requests/` |
| Business logic | `app/Services/` (`Dental/`, `Hkd/`, `Hr/` theo miền) |
| Truy vấn DB, quan hệ, scope | `app/Models/` (+ `app/Models/Concerns/` cho trait dùng chung) |
| Hằng số trạng thái | `app/Enums/` |
| Helper / Utils thuần | `app/Support/` (PHP), `resources/js/utils/` (JS) |
| Xuất Excel | `app/Exports/` |
| Job nền | `app/Jobs/` |
| Lệnh artisan | `app/Console/Commands/` |
| Cấu hình tĩnh (menu, hằng số) | `config/` |

**Cấm:**
- Tạo file ngoài cấu trúc trên (không có thư mục "tạm", "misc", "new" ở gốc dự án).
- Trộn UI và business logic trong cùng một file.
- Đặt query Eloquent trong View, hoặc echo HTML trong Controller/Service.
- Nhét helper dùng chung vào Controller — tách ra `app/Support/`.
- Sửa file trong `vendor/`, `node_modules/`, `public/build/`.

Trước khi tạo file mới: tìm xem đã có Service/Support/component tương đương chưa. Tái sử
dụng thắng viết mới.

## 3. View & tương tác web

Quy tắc đầy đủ nằm ở [`../rules/core.md`](../rules/core.md) §5 (View là Dumb UI · web không
reload trang · fetch + CSRF + `preventDefault` + cập nhật DOM cục bộ). Không nhắc lại ở đây.

## 4. Git Flow

| Việc | Quy ước |
|---|---|
| Nhánh chính | `main` — luôn ở trạng thái deploy được |
| Nhánh làm việc | `feat/<mô-tả-ngắn>`, `fix/<mô-tả-ngắn>`, `refactor/<…>`, `chore/<…>` |
| Commit | Conventional Commits + **scope theo module**: `feat(schedule): …`, `fix(clinical): …`, `refactor(ui): …` |
| Ngôn ngữ commit | Tiếng Việt (theo lịch sử repo hiện có) |
| Kích thước commit | Một commit = một thay đổi có nghĩa; không gộp refactor + tính năng |
| Push / deploy | **Chỉ khi người dùng yêu cầu rõ ràng.** Không tự động commit sau khi sửa code |
| Không bao giờ | `--no-verify`, `push --force` lên `main`, commit `.env` hay file sinh ra (`public/build/`, `node_modules/`) |

## 5. Quy trình làm một task

1. Đọc `.claude/plans/00-context-memory.md` để biết đang ở đâu.
2. Xác định module và tầng bị chạm → chọn đúng thư mục theo bảng ở mục 2.
3. Đọc code lân cận, bám theo idiom sẵn có thay vì áp phong cách mới.
4. Sửa code. Nếu chạm DB → theo [database-safety.md](database-safety.md).
5. Kiểm tra: `php vendor/bin/pint`, chạy test liên quan (xem cờ thu gọn output ở
   [coding-standards.md](coding-standards.md)).
6. Cập nhật `00-context-memory.md`: task, blocker, quyết định mới.
7. Chỉ commit khi được yêu cầu.

## 6. Khi cần đổi kiến trúc

Không tự ý đổi stack đã chốt (Laravel 12, MySQL, Tailwind 3, Vite, Blade+Alpine cho UI mới).
Muốn đổi: ghi đề xuất kèm **trade-off + blast radius** vào Decision Log của
`00-context-memory.md` và hỏi người dùng trước.
