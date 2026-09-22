# Decision Log — lịch sử đầy đủ

5 quyết định gần nhất nằm ở [00-context-memory.md](00-context-memory.md). Khi bảng đó quá 5 dòng,
đẩy dòng cũ nhất xuống đây. Mỗi dòng: **quyết định — lý do — hệ quả**, ngày tuyệt đối.

| Ngày | Quyết định | Lý do | Hệ quả |
|---|---|---|---|
| 2026-08-26 | Khởi tạo `.claude/plans` + `.claude/rules` làm nguồn tham chiếu kiến trúc | Dự án chưa có `CLAUDE.md`/ADR, cần chỗ ghi quyết định thay vì để trong đầu | Mọi quyết định kiến trúc từ đây phải ghi vào Decision Log mới được coi là đã chốt |
| 2026-08-26 | Phase 2: chuyển UI sang **Blade + Alpine + JS thuần**, bỏ dần Inertia/Vue | Giảm phụ thuộc build phức tạp | Phải viết lại ~140 trang; mất điều hướng SPA của Inertia → bù bằng fetch + cập nhật DOM cục bộ. Hai lớp UI song song trong suốt Phase 2 |
| 2026-08-26 | Menu sidebar tách thành `config/menu.php` + `App\Support\Menu` | Bản PHP tương đương `menuConfig.js`, lọc quyền ở server thay vì ở client | Thêm mục menu = sửa `config/menu.php`, không sửa JS. (Hai file này **chưa tồn tại** trong working tree tính đến 2026-09-22) |
| 2026-08-26 | Giữ nguyên stack Laravel 12 + MySQL + Tailwind 3 + Vite | Đã chốt sau khi dựng xong hạ tầng | Đổi stack phải có ADR mới ghi vào bảng này trước khi viết code |
| — (commit `6d9040c`, `0884a1f`) | Đăng ký khám chỉ thuộc đúng ngày hôm đó; hết ngày tự chốt "Hoàn thành" | Quy tắc nghiệp vụ của lễ tân | `registration_date` luôn là ngày hiện tại; không cho tạo/sửa đăng ký của ngày khác |
