# Database Safety

## ⚠️ Cảnh báo số 1

**`.env` ở máy local đang trỏ tới DATABASE PRODUCTION.**
Mọi lệnh `php artisan` chạy ở local đều tác động **dữ liệu thật của phòng khám**.
Trước bất kỳ lệnh nào có khả năng ghi: kiểm tra `DB_HOST`/`DB_DATABASE` trong `.env`,
chạy dry-run, rồi **hỏi xác nhận người dùng**.

## 1. Lệnh CẤM tuyệt đối (không chạy, kể cả khi "chắc là an toàn")

```
php artisan migrate:fresh          php artisan migrate:reset
php artisan migrate:refresh        php artisan db:wipe
php artisan db:seed  (trên prod)   php artisan migrate:rollback
DROP / TRUNCATE / DELETE FROM ... (không WHERE) / UPDATE ... (không WHERE)
```

Nếu thật sự cần: trình bày chính xác lệnh, bảng và số dòng bị ảnh hưởng, có rollback hay
không → chờ người dùng xác nhận tường minh.

## 2. Được phép tự do (chỉ đọc)

`SELECT`, `EXPLAIN`, xem schema, đọc file migration, `php artisan migrate:status`,
`php artisan tinker` chỉ để đọc, mọi lệnh có `--dry-run`/`--pretend`.

## 3. Quy trình tạo & chạy Migration

1. **Tạo**: `php artisan make:migration <verb>_<đối_tượng>_table` — tên nói rõ hành động
   (`add_indexes_to_...`, `make_phone_nullable_in_patients`).
2. **Không sửa migration đã chạy trên production.** Sai thì viết migration mới bù.
3. **Bắt buộc có `down()` chạy được.** Migration không rollback được phải ghi rõ lý do trong
   comment đầu file và được người dùng đồng ý.
4. **Đọc file migration trước khi chạy** — biết chính xác nó làm gì.
5. **Backup trước khi chạy trên production.** Không có backup = không chạy.
6. **Dry-run**: `php artisan migrate --pretend | tail -40` để xem SQL sẽ chạy.
7. **Xin xác nhận**, rồi mới `php artisan migrate --force`.
8. **Verify sau khi chạy**: `migrate:status`, đếm số dòng bảng liên quan, kiểm tra một
   bất biến nghiệp vụ (ví dụ tổng thu không đổi).

## 4. Migration đổi dữ liệu (backfill)

- Tách riêng khỏi migration đổi schema.
- Chạy theo lô (`chunkById`), không `update()` cả bảng một lần.
- Phải **idempotent**: chạy lại lần 2 không nhân đôi/không hỏng dữ liệu.
- Phải có bước đối soát: số dòng trước/sau, checksum, hoặc tổng nghiệp vụ.
- Phải có đường quay lại (cột cũ giữ nguyên tới khi xác nhận, hoặc bảng snapshot).

## 5. Soft delete

- Xoá dữ liệu nghiệp vụ = **xoá mềm** (`deleted_at`), không `forceDelete`.
- Xoá có ảnh hưởng lớn đi qua `pending_deletions` (xoá có duyệt).
- Kiểm tra mọi truy vấn liên quan có loại trừ đúng bản ghi đã xoá mềm
  (kể cả raw query, view, báo cáo tổng hợp).
- UNIQUE constraint phải tính tới bản ghi đã xoá mềm — tránh bản cũ chặn tạo bản mới.

## 6. Audit log bất biến

- `attendance_audit_logs`, `payroll_audit_logs`, activitylog: **append-only**.
  Không viết code `update`/`delete` lên các bảng này.
- Ghi đủ: ai (actor), lúc nào, giá trị trước → sau.

## 7. Index & hiệu năng

- Thêm index bằng migration riêng, đặt tên rõ (`add_indexes_for_patients_list_query`).
- Trước khi thêm: `EXPLAIN` truy vấn thật để chứng minh index có tác dụng.
- Bảng lớn: thêm index vào giờ thấp điểm, cân nhắc khoá bảng.

## 8. Transaction & toàn vẹn

- Mọi thao tác chạm nhiều bảng (tạo hoá đơn + thanh toán + cập nhật KHDT) bọc `DB::transaction()`.
- Ràng buộc quan trọng (FK, UNIQUE) phải enforce ở **tầng DB**, không chỉ validate ở app.
- Tiền: dùng decimal/integer; không dùng float.

## 9. Test

Test **không** được chạy trên DB production. Kiểm tra `phpunit.xml` dùng connection riêng
(sqlite in-memory hoặc DB test) trước khi chạy `php artisan test`.

## 10. Checklist trước mọi thao tác ghi dữ liệu

- [ ] Đã xác nhận đang trỏ DB nào?
- [ ] Đã có backup chưa?
- [ ] Đã chạy dry-run và đọc SQL sinh ra chưa?
- [ ] Có rollback không? Rollback thế nào?
- [ ] Đã hỏi và được người dùng xác nhận chưa?
- [ ] Sau khi chạy sẽ verify bằng cách nào?
