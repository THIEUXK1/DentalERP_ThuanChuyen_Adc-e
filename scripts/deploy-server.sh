#!/bin/sh
# Chạy TRÊN server production. Gọi qua scripts/deploy.ps1 từ máy dev, hoặc trực tiếp:
#   sh /var/www/DentalERP/scripts/deploy-server.sh <commit-truoc-khi-push>
#
# Nguyên tắc: không được làm gián đoạn người đang dùng.
#  - Build vào thư mục tạm rồi tráo đổi, vì `vite build` xoá sạch public/build trước khi
#    ghi lại → manifest.json biến mất ~20s và user dính lỗi 500 (đã xảy ra 15/09/2026).
#  - Giữ lại asset cũ để tab đang mở không lỗi tải chunk.
#  - Chỉ rebuild cache khi routes/config/views thật sự đổi (bỏ qua 23/10 làm route mới
#    không đăng ký, modal 404).
#  - reload chứ không restart php-fpm.
set -e
cd /var/www/DentalERP

PREV="${1:-HEAD~1}"
FAILED=0

say() { printf '%s\n' "$*"; }
step() { printf '\n── %s\n' "$*"; }

step "1/6 Migration"
PENDING=$(php artisan migrate:status 2>/dev/null | grep -c 'Pending' || true)
if [ "$PENDING" -gt 0 ]; then
    say "Có $PENDING migration chờ chạy → backup DB trước"
    mkdir -p /var/backups/dentalerp
    DUMP="/var/backups/dentalerp/before_deploy_$(date +%Y%m%d_%H%M%S).dump"
    sudo -u postgres pg_dump -Fc dental_erp_db > "$DUMP"
    [ -s "$DUMP" ] || { say "BACKUP THẤT BẠI → dừng, không migrate"; exit 1; }
    say "backup: $DUMP ($(du -h "$DUMP" | cut -f1))"
    php artisan migrate --force
else
    say "Không có migration chờ chạy"
fi

step "2/6 Build asset (tráo đổi, không đè lên thư mục đang phục vụ)"
rm -rf public/build_new /tmp/oldbuild
npm run build -- --outDir public/build_new --emptyOutDir --base=/build/ > /tmp/build.log 2>&1 \
    || { say "BUILD LỖI:"; tail -20 /tmp/build.log; exit 1; }
grep -q 'built in' /tmp/build.log || { say "BUILD KHÔNG HOÀN TẤT:"; tail -20 /tmp/build.log; exit 1; }
grep 'built in' /tmp/build.log | tail -1

# --base=/build/ là bắt buộc; thiếu nó CSS sẽ trỏ /build_new/... sau khi tráo.
if grep -rl 'build_new' public/build_new 2>/dev/null | head -1 | grep -q .; then
    say "HUỶ BỎ: build ra có tham chiếu /build_new/ → không tráo đổi"; exit 1
fi

step "3/6 Kiểm tra manifest trước khi tráo"
php -r '
$p = "public/build_new/manifest.json";
if (! file_exists($p)) { fwrite(STDERR, "không có manifest\n"); exit(1); }
$m = json_decode(file_get_contents($p), true);
$e = $m["resources/js/app.js"]["file"] ?? null;
if (! $e || ! file_exists("public/build_new/".$e)) { fwrite(STDERR, "entry hỏng\n"); exit(1); }
$n = 0; foreach ($m as $v) { if (! empty($v["file"]) && ! file_exists("public/build_new/".$v["file"])) $n++; }
if ($n > 0) { fwrite(STDERR, "có $n entry trỏ file không tồn tại\n"); exit(1); }
echo "manifest OK (entry $e)\n";'

step "4/6 Tráo đổi + giữ asset cũ cho tab đang mở"
cp -rn public/build/assets/. public/build_new/assets/ 2>/dev/null || true
mv public/build /tmp/oldbuild && mv public/build_new public/build
say "tổng asset: $(ls public/build/assets | wc -l)"

step "5/6 Cache (chỉ khi cần)"
CHANGED=$(git diff --name-only "$PREV" HEAD -- routes/ config/ resources/views/ bootstrap/ 2>/dev/null | wc -l)
if [ "$CHANGED" -gt 0 ]; then
    say "$CHANGED file routes/config/views đổi → rebuild cache"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    systemctl reload php8.3-fpm
    say "đã reload php-fpm (graceful)"
else
    say "routes/config/views không đổi → giữ cache hiện có"
    say "(opcache.validate_timestamps=On nên code PHP mới tự có hiệu lực sau ~2s)"
fi

step "6/6 Kiểm chứng"
say "HEAD:            $(git log --oneline -1)"

MISSING=0
for f in $(ls /tmp/oldbuild/assets); do
    [ -e "public/build/assets/$f" ] || MISSING=$((MISSING + 1))
done
say "asset cũ bị mất:  $MISSING $([ "$MISSING" -eq 0 ] && echo '(tab đang mở an toàn)' || echo '← CẢNH BÁO')"
[ "$MISSING" -eq 0 ] || FAILED=1

CODE=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/login)
say "/login:          $CODE"
[ "$CODE" = "200" ] || FAILED=1

for s in php8.3-fpm nginx postgresql; do
    say "$s: $(systemctl is-active $s)"
done

ERR5=$(grep "$(date '+%d/%b/%Y')" /var/log/nginx/access.log 2>/dev/null | awk '$9 ~ /^5/' | wc -l)
say "lỗi 5xx hôm nay: $ERR5"

VITE=$(grep -c 'Vite manifest not found' storage/logs/laravel.log 2>/dev/null || echo 0)
say "lỗi Vite manifest (tích luỹ): $VITE"

SESS=$(php artisan tinker --execute="echo DB::table('sessions')->where('last_activity','>',now()->subHours(2)->timestamp)->count();" 2>/dev/null | tail -1)
say "phiên đang hoạt động: $SESS"

printf '\n'
if [ "$FAILED" -eq 0 ]; then
    say "✓ DEPLOY OK"
else
    say "✗ DEPLOY CÓ VẤN ĐỀ — xem các dòng cảnh báo ở trên."
    say "  Khôi phục asset: rm -rf public/build && mv /tmp/oldbuild public/build"
    exit 1
fi
