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

$ErrorActionPreference = 'Stop'
$keyUnix = $Key -replace '\\', '/'   # ssh qua MSYS ăn backslash → luôn dùng forward slash

function Step($m) { Write-Host "`n── $m" -ForegroundColor Cyan }
function Die($m)  { Write-Host "`n✗ $m" -ForegroundColor Red; exit 1 }

# ── 1. Tree phải sạch ───────────────────────────────────────────────
Step '1/5 Kiểm tra working tree'
$dirty = git status --porcelain | Where-Object { $_ -notmatch '^\?\? ' }
if ($dirty) {
    Write-Host ($dirty -join "`n") -ForegroundColor Yellow
    Die "Còn file chưa commit. Commit trước rồi deploy (script cố ý không tự commit)."
}
$head = (git rev-parse --short HEAD).Trim()
Write-Host "HEAD: $(git log --oneline -1)"

# ── 2. Build + test ở local ─────────────────────────────────────────
# Build lỗi ngay trên server sẽ xoá asset giữa chừng → phải chặn từ đây.
if (-not $SkipChecks) {
    Step '2/5 Build + test ở local'
    $build = npm run build --silent 2>&1 | Out-String
    if ($build -notmatch 'built in') { Write-Host $build; Die 'Build thất bại.' }
    Write-Host ($build -split "`n" | Select-String 'built in')

    $test = php artisan test --stop-on-failure 2>&1 | Out-String
    if ($test -notmatch 'Tests:\s+\d+ passed') { Write-Host ($test -split "`n" | Select-Object -Last 25); Die 'Test thất bại.' }
    Write-Host ($test -split "`n" | Select-String 'Tests:')
} else {
    Step '2/5 Bỏ qua build + test (-SkipChecks)'
}

# ── 3. Push GitHub ──────────────────────────────────────────────────
Step '3/5 Push GitHub'
git push origin main
if ($LASTEXITCODE -ne 0) { Die 'Push GitHub thất bại.' }

# ── 4. Push sang server ─────────────────────────────────────────────
# Lấy commit server ĐANG chạy trước khi push, để script bên kia biết so sánh
# routes/config/views từ mốc nào mà quyết định có rebuild cache không.
Step '4/5 Push sang server'
$prev = (ssh -i $keyUnix $Server "cd $AppPath && git rev-parse --short HEAD").Trim()
Write-Host "server đang ở: $prev → $head"
if ($prev -eq $head) {
    Write-Host 'Server đã ở đúng commit này — không có gì để đẩy.' -ForegroundColor Yellow
    exit 0
}

$env:GIT_SSH_COMMAND = "ssh -i `"$keyUnix`""
git push "ssh://$Server$AppPath" main
if ($LASTEXITCODE -ne 0) { Die 'Push sang server thất bại (tree trên server có sạch không?).' }

# ── 5. Chạy deploy trên server ──────────────────────────────────────
Step '5/5 Deploy trên server'
ssh -i $keyUnix $Server "sh $AppPath/scripts/deploy-server.sh $prev"
if ($LASTEXITCODE -ne 0) { Die "Deploy trên server báo lỗi. Khôi phục asset: ssh -i $keyUnix $Server `"rm -rf $AppPath/public/build && mv /tmp/oldbuild $AppPath/public/build`"" }

Write-Host "`n✓ Xong. $head đã chạy trên production." -ForegroundColor Green
