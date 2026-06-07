<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\PatientInvoice;
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
}
