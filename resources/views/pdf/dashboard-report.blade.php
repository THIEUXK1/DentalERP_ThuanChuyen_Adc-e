<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
    .header { text-align: center; border-bottom: 2px solid #0d9488; padding-bottom: 10px; margin-bottom: 14px; }
    .header h1 { font-size: 17px; color: #0d9488; font-weight: bold; }
    .doc-title { font-size: 15px; font-weight: bold; text-align: center; margin: 14px 0 10px; }
    .meta { text-align: center; font-size: 10px; color: #666; margin-bottom: 16px; }
    .section-title { font-size: 12px; font-weight: bold; color: #0f766e; background: #f0fdfa; padding: 5px 8px; margin: 16px 0 8px; border-left: 3px solid #0d9488; }
    .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 6px; }
    .kpi-card { border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 10px; }
    .kpi-card .label { font-size: 9px; color: #888; }
    .kpi-card .value { font-size: 15px; font-weight: bold; color: #111; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 10px; }
    th { background: #f0fdfa; border: 1px solid #ccfbf1; padding: 5px 7px; text-align: left; color: #0f766e; }
    td { border: 1px solid #e5e7eb; padding: 4px 7px; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #888; }
    @page { margin: 16mm; }
</style>
</head>
<body>
<div class="header">
    <h1>Dental Clinic ERP</h1>
    <p style="font-size:10px;color:#666;">{{ $branchName }}</p>
</div>

<div class="doc-title">BÁO CÁO TỔNG QUAN</div>
<div class="meta">Ngày: {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} &bull; Xuất lúc: {{ now()->setTimezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</div>

<div class="section-title">KPI TỔNG QUAN</div>
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="label">Lịch hẹn</div>
        <div class="value">{{ $kpis['todayAppts'] }}</div>
    </div>
    @if($canFinancial)
    <div class="kpi-card">
        <div class="label">Doanh thu</div>
        <div class="value">{{ number_format($kpis['todayRevenue'], 0, ',', '.') }} ₫</div>
    </div>
    <div class="kpi-card">
        <div class="label">Tổng công nợ</div>
        <div class="value">{{ number_format($kpis['totalOutstanding'], 0, ',', '.') }} ₫</div>
    </div>
    @endif
    <div class="kpi-card">
        <div class="label">Lead mới (7 ngày)</div>
        <div class="value">{{ $kpis['newLeads'] }}</div>
    </div>
    <div class="kpi-card">
        <div class="label">Khách hàng đang hoạt động</div>
        <div class="value">{{ $kpis['activePatients'] }}</div>
    </div>
    <div class="kpi-card">
        <div class="label">Follow-up chưa xử lý</div>
        <div class="value">{{ $pendingTasksCount }}</div>
    </div>
    <div class="kpi-card">
        <div class="label">Tỷ lệ chốt kế hoạch điều trị</div>
        <div class="value">{{ $treatmentPlanConversion['rate'] }}%</div>
    </div>
</div>

@if($canFinancial && (count($revenueByDoctor) > 0 || count($revenueByService) > 0))
<div class="section-title">DOANH THU (30 NGÀY GẦN NHẤT)</div>
<div class="two-col">
    @if(count($revenueByDoctor) > 0)
    <div>
        <p style="font-weight:bold;font-size:10px;margin-bottom:4px;">Theo bác sĩ</p>
        <table>
            <thead><tr><th>Bác sĩ</th><th style="text-align:right">Doanh thu</th></tr></thead>
            <tbody>
                @foreach($revenueByDoctor as $r)
                <tr><td>{{ $r['name'] }}</td><td style="text-align:right">{{ number_format($r['revenue'], 0, ',', '.') }} ₫</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @if(count($revenueByService) > 0)
    <div>
        <p style="font-weight:bold;font-size:10px;margin-bottom:4px;">Theo dịch vụ</p>
        <table>
            <thead><tr><th>Dịch vụ</th><th style="text-align:right">Doanh thu</th></tr></thead>
            <tbody>
                @foreach($revenueByService as $r)
                <tr><td>{{ $r['name'] }}</td><td style="text-align:right">{{ number_format($r['revenue'], 0, ',', '.') }} ₫</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endif

@if(count($apptBreakdown) > 0)
<div class="section-title">LỊCH HẸN THEO TRẠNG THÁI</div>
<table>
    <thead><tr><th>Trạng thái</th><th style="text-align:right">Số lượng</th></tr></thead>
    <tbody>
        @foreach($apptBreakdown as $r)
        <tr><td>{{ $r['status'] }}</td><td style="text-align:right">{{ $r['count'] }}</td></tr>
        @endforeach
    </tbody>
</table>
@endif

@if($canFinancial && count($todayPayments) > 0)
<div class="section-title">THANH TOÁN TRONG NGÀY</div>
<table>
    <thead><tr><th>Giờ</th><th>Khách hàng</th><th>Hóa đơn</th><th>Hình thức</th><th style="text-align:right">Số tiền</th><th>Người thu</th></tr></thead>
    <tbody>
        @foreach($todayPayments as $p)
        <tr>
            <td>{{ $p['time'] }}</td>
            <td>{{ $p['patient'] }}</td>
            <td>{{ $p['invoice_code'] ?? '—' }}</td>
            <td>{{ $p['method_label'] }}</td>
            <td style="text-align:right">{{ number_format($p['amount'], 0, ',', '.') }} ₫</td>
            <td>{{ $p['creator'] ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if(count($leadFunnel) > 0)
<div class="section-title">PIPELINE LEAD (30 NGÀY)</div>
<table>
    <thead><tr><th>Trạng thái</th><th style="text-align:right">Số lượng</th></tr></thead>
    <tbody>
        @foreach($leadFunnel as $r)
        <tr><td>{{ $r['status'] }}</td><td style="text-align:right">{{ $r['count'] }}</td></tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer"><em>Báo cáo được xuất từ hệ thống Dental Clinic ERP</em></div>
</body>
</html>
