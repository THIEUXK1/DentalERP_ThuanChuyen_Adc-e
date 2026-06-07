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
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 14px; }
    .info-row { display: flex; gap: 6px; font-size: 10px; }
    .info-row .label { color: #666; min-width: 80px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; }
    th { background: #f0fdfa; border: 1px solid #ccfbf1; padding: 5px 7px; text-align: left; color: #0f766e; }
    td { border: 1px solid #e5e7eb; padding: 4px 7px; }
    .totals { margin-top: 10px; float: right; width: 200px; font-size: 11px; }
    .grand { font-weight: bold; border-top: 1px solid #0d9488; color: #0d9488; }
    .payments { margin-top: 14px; }
    .footer { margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; text-align: center; font-size: 10px; }
    .footer .sig { padding-top: 35px; }
    .stamp { margin-top: 10px; text-align: center; font-size: 10px; color: #666; }
    @page { margin: 20mm; }
</style>
</head>
<body>
<div class="header">
    <h1>Dental Clinic ERP</h1>
    <p style="font-size:10px;color:#666;">{{ $invoice->branch->name }} &bull; {{ $invoice->branch->phone ?? '' }}</p>
</div>

<div class="doc-title">PHIẾU THU TIỀN</div>

<div class="info-grid">
    <div>
        <div class="info-row"><span class="label">Mã hóa đơn:</span><span style="font-weight:600">{{ $invoice->code }}</span></div>
        <div class="info-row"><span class="label">Khách hàng:</span><span>{{ $invoice->patient->full_name }}</span></div>
        <div class="info-row"><span class="label">SĐT:</span><span>{{ $invoice->patient->phone }}</span></div>
    </div>
    <div>
        <div class="info-row"><span class="label">Ngày:</span><span>{{ now()->format('d/m/Y') }}</span></div>
        @if($invoice->treatmentPlan)
        <div class="info-row"><span class="label">Kế hoạch:</span><span>{{ $invoice->treatmentPlan->code }}</span></div>
        @endif
        <div class="info-row"><span class="label">Chi nhánh:</span><span>{{ $invoice->branch->name }}</span></div>
    </div>
</div>

@if($invoice->treatmentPlan)
<table>
    <thead><tr><th>Dịch vụ</th><th>Răng</th><th>SL</th><th style="text-align:right">Đơn giá</th><th style="text-align:right">Thành tiền</th></tr></thead>
    <tbody>
        @foreach($invoice->treatmentPlan->items as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td>{{ $item->tooth_number ?? '—' }}</td>
            <td style="text-align:center">{{ $item->quantity }}</td>
            <td style="text-align:right">{{ number_format($item->unit_price, 0, ',', '.') }} ₫</td>
            <td style="text-align:right">{{ number_format($item->subtotal, 0, ',', '.') }} ₫</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="totals">
    <table>
        <tr style="border:none;"><td>Tổng cộng:</td><td style="text-align:right">{{ number_format($invoice->subtotal, 0, ',', '.') }} ₫</td></tr>
        @if($invoice->discount > 0)
        <tr style="border:none;"><td>Giảm giá:</td><td style="text-align:right">-{{ number_format($invoice->discount, 0, ',', '.') }} ₫</td></tr>
        @endif
        <tr class="grand" style="border:none;"><td>Phải thu:</td><td style="text-align:right">{{ number_format($invoice->total, 0, ',', '.') }} ₫</td></tr>
        <tr style="border:none;color:#166534;"><td>Đã thu:</td><td style="text-align:right">{{ number_format($invoice->amount_paid, 0, ',', '.') }} ₫</td></tr>
        <tr style="border:none;color:#dc2626;font-weight:bold;"><td>Còn nợ:</td><td style="text-align:right">{{ number_format($invoice->amountDue(), 0, ',', '.') }} ₫</td></tr>
    </table>
</div>

<div style="clear:both"></div>

@if($invoice->payments->count() > 0)
<div class="payments">
    <p style="font-weight:bold;margin-bottom:6px;font-size:10px;">Lịch sử thanh toán:</p>
    <table>
        <thead><tr><th>Ngày</th><th>Phương thức</th><th style="text-align:right">Số tiền</th><th>Ghi chú</th></tr></thead>
        <tbody>
            @foreach($invoice->payments as $pay)
            <tr>
                <td>{{ $pay->payment_date->format('d/m/Y') }}</td>
                <td>{{ $pay->method->label() }}</td>
                <td style="text-align:right">{{ number_format($pay->amount, 0, ',', '.') }} ₫</td>
                <td>{{ $pay->notes ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="footer">
    <div><strong>Khách hàng</strong><div class="sig">Ký và ghi rõ họ tên</div></div>
    <div><strong>Nhân viên thu ngân</strong><div class="sig">Ký tên</div></div>
</div>
<div class="stamp"><em>Phiếu thu được in từ hệ thống Dental Clinic ERP</em></div>
</body>
</html>
