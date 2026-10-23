# Deploy DentalERP lên production bằng một lệnh:
#   .\scripts\deploy.ps1
#
# Chỉ deploy code ĐÃ COMMIT — không tự commit, để không gộp lẫn file đang sửa dở
# hay file secret. Muốn bỏ qua test/build ở local: -SkipChecks (không khuyến khích).

param(
    [string] $Server  = 'root@103.176.179.167',
    [string] $AppPath = '/var/www/DentalERP',
    [string] $Key     = "$HOME\.ssh\dental_erp_backup",
    [switch] $SkipChecks
)

# KHÔNG dùng ErrorActionPreference='Stop': git/ssh ghi tiến trình ra stderr, PS 5.1 sẽ
# coi đó là lỗi và dừng script dù lệnh chạy thành công. Kiểm $LASTEXITCODE thay vì.
$ErrorActionPreference = 'Continue'
$keyUnix = $Key -replace '\\', '/'   # ssh qua MSYS ăn backslash → luôn dùng forward slash

# Dùng ASCII: console PS 5.1 hiển thị ký tự khung/✓ thành mojibake.
function Step($m) { Write-Host "`n-- $m" -ForegroundColor Cyan }
function Die($m)  { Write-Host "`n[LOI] $m" -ForegroundColor Red; exit 1 }

# 1. Tree phải sạch
Step '1/5 Kiem tra working tree'
$dirty = git status --porcelain | Where-Object { $_ -notmatch '^\?\? ' }
if ($dirty) {
    Write-Host ($dirty -join "`n") -ForegroundColor Yellow
    Die "Con file chua commit. Commit truoc roi deploy (script co y khong tu commit)."
}
$head = (git rev-parse --short HEAD).Trim()
Write-Host "HEAD: $(git log --oneline -1)"

# 2. Build + test ở local
# Build lỗi ngay trên server sẽ xoá asset giữa chừng → phải chặn từ đây.
if (-not $SkipChecks) {
    Step '2/5 Build + test o local'
    $build = npm run build --silent 2>&1 | Out-String
    if ($build -notmatch 'built in') { Write-Host $build; Die 'Build that bai.' }
    Write-Host ($build -split "`n" | Select-String 'built in')

    $test = php artisan test --stop-on-failure 2>&1 | Out-String
    if ($test -notmatch 'Tests:\s+\d+ passed') { Write-Host ($test -split "`n" | Select-Object -Last 25); Die 'Test that bai.' }
    Write-Host ($test -split "`n" | Select-String 'Tests:')
} else {
    Step '2/5 Bo qua build + test (-SkipChecks)'
}

# 3. Push GitHub
# `2>&1` là bắt buộc: git ghi tiến trình ra stderr, không gộp lại thì PS 5.1 in ra
# một khối NativeCommandError đỏ trông như script đã lỗi dù push thành công.
Step '3/5 Push GitHub'
git push origin main 2>&1 | Write-Host
if ($LASTEXITCODE -ne 0) { Die 'Push GitHub that bai.' }

# 4. Push sang server
# Lấy commit server ĐANG chạy trước khi push, để script bên kia biết so sánh
# routes/config/views từ mốc nào mà quyết định có rebuild cache không.
Step '4/5 Push sang server'
$prev = (ssh -i $keyUnix $Server "cd $AppPath && git rev-parse --short HEAD" 2>$null).Trim()
Write-Host "server dang o: $prev -> $head"
if ($prev -eq $head) {
    Write-Host 'Server da o dung commit nay -- khong co gi de day.' -ForegroundColor Yellow
    exit 0
}

$env:GIT_SSH_COMMAND = "ssh -i `"$keyUnix`""
git push "ssh://$Server$AppPath" main 2>&1 | Write-Host
if ($LASTEXITCODE -ne 0) { Die 'Push sang server that bai (tree tren server co sach khong?).' }

# 5. Chạy deploy trên server
Step '5/5 Deploy tren server'
ssh -i $keyUnix $Server "sh $AppPath/scripts/deploy-server.sh $prev" 2>&1 | Write-Host
if ($LASTEXITCODE -ne 0) { Die "Deploy tren server bao loi. Khoi phuc asset: ssh -i $keyUnix $Server `"rm -rf $AppPath/public/build && mv /tmp/oldbuild $AppPath/public/build`"" }

Write-Host "`n[OK] Xong. $head da chay tren production." -ForegroundColor Green
