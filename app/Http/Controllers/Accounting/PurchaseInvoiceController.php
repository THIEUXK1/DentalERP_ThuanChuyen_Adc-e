<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FundAccount;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Services\PurchaseInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseInvoiceController extends Controller
{
    public function __construct(private PurchaseInvoiceService $svc) {}

    public function index(Request $request): Response
    {
        $this->authorize('accounting.view');

        $query = PurchaseInvoice::with('supplier')
            ->orderByDesc('invoice_date');

        if ($request->supplier_id) $query->where('supplier_id', $request->supplier_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->from) $query->where('invoice_date', '>=', $request->from);
        if ($request->to) $query->where('invoice_date', '<=', $request->to);

        $invoices = $query->paginate(20)->through(fn ($i) => [
            'id'           => $i->id,
            'code'         => $i->code,
            'supplier'     => $i->supplier->name,
            'supplier_id'  => $i->supplier_id,
            'invoice_date' => $i->invoice_date->format('d/m/Y'),
            'due_date'     => $i->due_date?->format('d/m/Y'),
            'total'        => $i->total,
            'paid_amount'  => $i->paid_amount,
            'amount_due'   => $i->amountDue(),
            'vat_amount'   => $i->vat_amount,
            'status'       => $i->status->value,
            'status_label' => $i->status->label(),
            'status_color' => $i->status->color(),
        ]);

        return Inertia::render('Accounting/PurchaseInvoices/Index', [
            'invoices'  => $invoices,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'filters'   => $request->only(['supplier_id', 'status', 'from', 'to']),
            'statuses'  => collect(\App\Enums\PurchaseInvoiceStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('accounting.manage');

        return Inertia::render('Accounting/PurchaseInvoices/Form', [
            'suppliers'    => Supplier::where('is_active', true)->orderBy('name')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'tax_code' => $s->tax_code]),
            'branches'     => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'fundAccounts' => FundAccount::where('is_active', true)->get()->map(fn ($a) => ['id' => $a->id, 'name' => $a->name]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('accounting.manage');

        $data = $request->validate([
            'supplier_id'     => 'required|exists:suppliers,id',
            'branch_id'       => 'nullable|exists:branches,id',
            'fund_account_id' => 'nullable|exists:fund_accounts,id',
            'invoice_date'    => 'required|date',
            'due_date'        => 'nullable|date|after_or_equal:invoice_date',
            'notes'           => 'nullable|string|max:1000',
            'items'           => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity'    => 'required|numeric|min:0.001',
            'items.*.unit_price'  => 'required|integer|min:0',
            'items.*.vat_rate'    => 'required|integer|between:0,20',
        ]);

        DB::transaction(function () use ($data) {
            $invoice = PurchaseInvoice::createWithCode([
                'supplier_id'     => $data['supplier_id'],
                'branch_id'       => $data['branch_id'] ?? null,
                'fund_account_id' => $data['fund_account_id'] ?? null,
                'invoice_date'    => $data['invoice_date'],
                'due_date'        => $data['due_date'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'status'          => 'draft',
                'created_by'      => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $amount = (int) round($item['quantity'] * $item['unit_price']);
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'vat_rate'    => $item['vat_rate'],
                    'amount'      => $amount,
                ]);
            }

            $this->svc->recalcTotals($invoice);
        });

        return redirect()->route('accounting.purchase-invoices.index')->with('success', 'Đã tạo hóa đơn mua hàng.');
    }

    public function show(PurchaseInvoice $purchaseInvoice): Response
    {
        $this->authorize('accounting.view');

        $purchaseInvoice->load(['supplier', 'branch', 'fundAccount', 'items', 'creator']);

        return Inertia::render('Accounting/PurchaseInvoices/Show', [
            'invoice' => [
                'id'            => $purchaseInvoice->id,
                'code'          => $purchaseInvoice->code,
                'supplier'      => $purchaseInvoice->supplier->name,
                'supplier_tax'  => $purchaseInvoice->supplier->tax_code,
                'branch'        => $purchaseInvoice->branch?->name,
                'fund_account'  => $purchaseInvoice->fundAccount?->name,
                'invoice_date'  => $purchaseInvoice->invoice_date->format('d/m/Y'),
                'due_date'      => $purchaseInvoice->due_date?->format('d/m/Y'),
                'subtotal'      => $purchaseInvoice->subtotal,
                'vat_amount'    => $purchaseInvoice->vat_amount,
                'total'         => $purchaseInvoice->total,
                'paid_amount'   => $purchaseInvoice->paid_amount,
                'amount_due'    => $purchaseInvoice->amountDue(),
                'payment_method'=> $purchaseInvoice->payment_method,
                'status'        => $purchaseInvoice->status->value,
                'status_label'  => $purchaseInvoice->status->label(),
                'status_color'  => $purchaseInvoice->status->color(),
                'notes'         => $purchaseInvoice->notes,
                'created_by'    => $purchaseInvoice->creator?->name,
                'items' => $purchaseInvoice->items->map(fn ($i) => [
                    'id'          => $i->id,
                    'description' => $i->description,
                    'quantity'    => $i->quantity,
                    'unit_price'  => $i->unit_price,
                    'vat_rate'    => $i->vat_rate,
                    'amount'      => $i->amount,
                ]),
            ],
            'fundAccounts' => FundAccount::where('is_active', true)->get()->map(fn ($a) => ['id' => $a->id, 'name' => $a->name]),
        ]);
    }

    public function addPayment(Request $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->authorize('accounting.manage');

        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'method' => 'required|in:cash,bank_transfer,card',
        ]);

        try {
            $this->svc->addPayment($purchaseInvoice, $data['amount'], $data['method']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã ghi nhận thanh toán.');
    }

    public function receive(PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->authorize('accounting.manage');

        try {
            $this->svc->receive($purchaseInvoice);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã xác nhận nhận hàng.');
    }

    public function cancel(PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->authorize('accounting.manage');

        try {
            $this->svc->cancel($purchaseInvoice);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã hủy hóa đơn.');
    }

    public function destroy(PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->authorize('accounting.manage');

        if (!in_array($purchaseInvoice->status->value, ['draft', 'cancelled'])) {
            return back()->with('error', 'Chỉ có thể xóa hóa đơn nháp hoặc đã hủy.');
        }

        $purchaseInvoice->items()->delete();
        $purchaseInvoice->delete();

        return redirect()->route('accounting.purchase-invoices.index')->with('success', 'Đã xóa hóa đơn.');
    }
}
