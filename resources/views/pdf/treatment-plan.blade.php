<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
    .header { text-align: center; border-bottom: 2px solid #0d9488; padding-bottom: 12px; margin-bottom: 16px; }
    .header h1 { font-size: 18px; color: #0d9488; font-weight: bold; }
    .header p { font-size: 10px; color: #666; margin-top: 2px; }
    .doc-title { font-size: 16px; font-weight: bold; text-align: center; margin: 16px 0 12px; color: #111; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
    .info-row { display: flex; gap: 6px; }
    .info-row .label { color: #666; min-width: 90px; }
    .info-row .value { font-weight: 600; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { background: #f0fdfa; border: 1px solid #ccfbf1; padding: 6px 8px; text-align: left; font-size: 10px; color: #0f766e; }
    td { border: 1px solid #e5e7eb; padding: 5px 8px; font-size: 10px; }
    tr:nth-child(even) td { background: #fafafa; }
    .totals { margin-top: 12px; float: right; width: 220px; }
    .totals table { border: none; }
    .totals td { border: none; padding: 3px 6px; }
    .totals .grand { font-weight: bold; font-size: 12px; color: #0d9488; border-top: 1px solid #0d9488; }
    .footer { margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; text-align: center; font-size: 10px; }
    .footer .sig { padding-top: 40px; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 9px; }
    .badge-gray { background: #f3f4f6; color: #374151; }
    .badge-green { background: #dcfce7; color: #166534; }
</style>
</head>
<body>
<div class="header">
    <h1>Dental Clinic ERP</h1>
    <p>{{ $treatmentPlan->branch->name }} &bull; {{ $treatmentPlan->branch->phone ?? '' }} &bull; {{ $treatmentPlan->branch->address ?? '' }}</p>
</div>

<div class="doc-title">BÁO GIÁ / KẾ HOẠCH ĐIỀU TRỊ</div>

<div class="info-grid">
    <div>
        <div class="info-row"><span class="label">Mã kế hoạch:</span><span class="value">{{ $treatmentPlan->code }}</span></div>
        <div class="info-row"><span class="label">Khách hàng:</span><span class="value">{{ $treatmentPlan->patient->full_name }}</span></div>
        <div class="info-row"><span class="label">SĐT:</span><span class="value">{{ $treatmentPlan->patient->phone }}</span></div>
    </div>
    <div>
        <div class="info-row"><span class="label">Ngày lập:</span><span class="value">{{ $treatmentPlan->created_at->format('d/m/Y') }}</span></div>
        <div class="info-row"><span class="label">Bác sĩ:</span><span class="value">{{ $treatmentPlan->doctor?->full_name ?? '—' }}</span></div>
        <div class="info-row"><span class="label">Trạng thái:</span><span class="value">{{ $treatmentPlan->status->label() }}</span></div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>STT</th>
            <th>Dịch vụ</th>
            <th>Số răng</th>
            <th>SL</th>
            <th style="text-align:right">Đơn giá (₫)</th>
            <th style="text-align:right">Thành tiền (₫)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($treatmentPlan->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $item->name }}</td>
            <td>{{ $item->tooth_number ?? 'Toàn bộ' }}</td>
            <td style="text-align:center">{{ $item->quantity }}</td>
            <td style="text-align:right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td style="text-align:right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    <table>
        <tr><td>Tổng dịch vụ:</td><td style="text-align:right">{{ number_format($treatmentPlan->total_amount, 0, ',', '.') }} ₫</td></tr>
        <tr><td>Giảm giá:</td><td style="text-align:right">-{{ number_format($treatmentPlan->discount_amount, 0, ',', '.') }} ₫</td></tr>
        <tr class="grand"><td>Thực thu:</td><td style="text-align:right">{{ number_format($treatmentPlan->net_total, 0, ',', '.') }} ₫</td></tr>
        @if($treatmentPlan->deposit_amount > 0)
        <tr><td>Đặt cọc:</td><td style="text-align:right">{{ number_format($treatmentPlan->deposit_amount, 0, ',', '.') }} ₫</td></tr>
        @endif
    </table>
</div>

<div style="clear:both; padding-top:20px;">
    @if($treatmentPlan->notes)
    <p style="font-size:10px; color:#666;"><strong>Ghi chú:</strong> {{ $treatmentPlan->notes }}</p>
    @endif
</div>

<div class="footer">
    <div>
        <strong>Khách hàng xác nhận</strong>
        <div class="sig">Ký và ghi rõ họ tên</div>
    </div>
    <div>
        <strong>Đại diện phòng khám</strong>
        <div class="sig">Ký, đóng dấu</div>
    </div>
</div>

</body>
</html>
