<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\PatientInvoice;
use App\Models\PatientPayment;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientPaymentController extends Controller
{
    public function __construct(private InvoiceService $svc) {}

    public function store(Request $request, PatientInvoice $invoice): RedirectResponse
    {
        $this->authorize('cashier.manage');

        $data = $request->validate([
            'amount' => 'required|integer',
            'method' => 'required|in:'.implode(',', array_column(PaymentMethod::cases(), 'value')),
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        // Refund (negative) requires special permission
        if ($data['amount'] < 0) {
            $this->authorize('cashier.approve_refund');

            if (abs($data['amount']) > $invoice->amount_paid) {
                return back()->with('error', 'Số tiền hoàn vượt quá số tiền đã thu.');
            }
        }

        // Block overpayment
        if ($data['amount'] > 0 && $data['amount'] > $invoice->amountDue()) {
            return back()->with('error', 'Số tiền thanh toán vượt quá số tiền còn nợ.');
        }

        try {
            $this->svc->addPayment($invoice, $data);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã ghi nhận thanh toán.');
    }

    public function updateMethod(Request $request, PatientPayment $payment): RedirectResponse
    {
        $this->authorize('cashier.manage');

        $data = $request->validate([
            'method' => 'required|in:'.implode(',', array_column(PaymentMethod::cases(), 'value')),
        ]);

        $payment->update(['method' => $data['method']]);

        return back()->with('success', 'Đã cập nhật hình thức thanh toán.');
    }

    public function updateDate(Request $request, PatientPayment $payment): RedirectResponse
    {
        $this->authorize('cashier.manage');

        $data = $request->validate([
            'payment_date' => 'required|date|before_or_equal:today',
        ]);

        $payment->update(['payment_date' => $data['payment_date']]);

        return back()->with('success', 'Đã cập nhật ngày thanh toán.');
    }

    public function reverse(PatientPayment $payment): RedirectResponse
    {
        $this->authorize('cashier.manage');
        $this->authorize('cashier.approve_refund');

        try {
            $this->svc->reversePayment($payment);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã hoàn tác khoản thanh toán.');
    }
}
