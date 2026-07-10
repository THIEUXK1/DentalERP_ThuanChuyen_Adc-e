<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DentalService;
use App\Models\DentalServiceStep;
use App\Models\InventoryItem;
use App\Models\InventoryServiceTemplate;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $svc) {}

    // ─── Danh mục vật tư ─────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $this->authorize('inventory.view');

        $query = InventoryItem::with('branch')
            ->when($request->search, fn ($q) => $q->where('name', 'ilike', '%' . $request->search . '%')
                ->orWhere('code', 'ilike', '%' . $request->search . '%'))
            ->when($request->branch_id, fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->low_stock, fn ($q) => $q->whereColumn('current_stock_qty', '<=', 'min_stock_qty'))
            ->where('is_active', true)
            ->orderBy('name');

        $items = $query->paginate(30)->through(fn ($i) => [
            'id'                => $i->id,
            'code'              => $i->code,
            'name'              => $i->name,
            'category'          => $i->category,
            'category_label'    => InventoryItem::categoryLabel($i->category),
            'unit'              => $i->unit,
            'branch_name'       => $i->branch?->name ?? 'Tất cả',
            'current_stock_qty' => $i->current_stock_qty,
            'min_stock_qty'     => $i->min_stock_qty,
            'unit_cost'         => $i->unit_cost,
            'stock_value'       => (int) round($i->current_stock_qty * $i->unit_cost),
            'is_low_stock'      => $i->isLowStock(),
        ]);

        return Inertia::render('Inventory/Index', [
            'items'      => $items,
            'branches'   => Branch::where('is_active', true)->get(['id', 'name']),
            'categories' => ['material', 'medicine', 'equipment', 'consumable', 'other'],
            'filters'    => $request->only(['search', 'branch_id', 'category', 'low_stock']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('inventory.manage');
        return Inertia::render('Inventory/Form', [
            'branches' => Branch::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('inventory.manage');

        $data = $request->validate([
            'name'          => 'required|string|max:200',
            'category'      => 'required|in:material,medicine,equipment,consumable,other',
            'unit'          => 'required|string|max:20',
            'branch_id'     => 'nullable|exists:branches,id',
            'min_stock_qty' => 'required|numeric|min:0',
            'unit_cost'     => 'required|integer|min:0',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $item = InventoryItem::createWithCode([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('inventory.show', $item)->with('success', 'Đã thêm vật tư.');
    }

    public function show(InventoryItem $inventory): Response
    {
        $this->authorize('inventory.view');

        $inventory->load('branch');

        $txs = InventoryTransaction::where('inventory_item_id', $inventory->id)
            ->with('creator')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($t) => [
                'id'               => $t->id,
                'transaction_type' => $t->transaction_type->value,
                'type_label'       => $t->transaction_type->label(),
                'type_color'       => $t->transaction_type->color(),
                'qty'              => $t->qty,
                'unit_cost'        => $t->unit_cost,
                'amount'           => $t->amount,
                'transaction_date' => $t->transaction_date->format('d/m/Y'),
                'document_no'      => $t->document_no,
                'source_type'      => $t->source_type,
                'notes'            => $t->notes,
                'creator'          => $t->creator?->name,
            ]);

        $templates = InventoryServiceTemplate::where('inventory_item_id', $inventory->id)
            ->with(['service', 'serviceStep'])
            ->where('is_active', true)
            ->get()
            ->map(fn ($t) => [
                'id'               => $t->id,
                'service_name'     => $t->service?->name,
                'step_name'        => $t->serviceStep?->step_name ?? 'Toàn dịch vụ',
                'qty_per_execution' => $t->qty_per_execution,
            ]);

        return Inertia::render('Inventory/Show', [
            'item' => [
                'id'                => $inventory->id,
                'code'              => $inventory->code,
                'name'              => $inventory->name,
                'category'          => $inventory->category,
                'category_label'    => InventoryItem::categoryLabel($inventory->category),
                'unit'              => $inventory->unit,
                'branch_name'       => $inventory->branch?->name ?? 'Tất cả',
                'current_stock_qty' => $inventory->current_stock_qty,
                'min_stock_qty'     => $inventory->min_stock_qty,
                'unit_cost'         => $inventory->unit_cost,
                'stock_value'       => (int) round($inventory->current_stock_qty * $inventory->unit_cost),
                'is_low_stock'      => $inventory->isLowStock(),
                'notes'             => $inventory->notes,
            ],
            'transactions' => $txs,
            'templates'    => $templates,
        ]);
    }

    public function update(Request $request, InventoryItem $inventory): RedirectResponse
    {
        $this->authorize('inventory.manage');

        $data = $request->validate([
            'name'          => 'required|string|max:200',
            'category'      => 'required|in:material,medicine,equipment,consumable,other',
            'unit'          => 'required|string|max:20',
            'branch_id'     => 'nullable|exists:branches,id',
            'min_stock_qty' => 'required|numeric|min:0',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $inventory->update($data);

        return back()->with('success', 'Đã cập nhật.');
    }

    // ─── Nhập kho ─────────────────────────────────────────────────────────────

    public function addStock(Request $request, InventoryItem $inventory): RedirectResponse
    {
        $this->authorize('inventory.manage');

        $data = $request->validate([
            'qty'         => 'required|numeric|min:0.001',
            'unit_cost'   => 'required|integer|min:0',
            'date'        => 'required|date',
            'document_no' => 'nullable|string|max:50',
            'notes'       => 'nullable|string|max:500',
        ]);

        $this->svc->addStock(
            $inventory,
            (float) $data['qty'],
            (int) $data['unit_cost'],
            $data['date'],
            auth()->user()->branch_id,
            'manual',
            null,
            $data['document_no'] ?? null,
            $data['notes'] ?? null
        );

        return back()->with('success', 'Đã nhập kho.');
    }

    // ─── Điều chỉnh tồn ───────────────────────────────────────────────────────

    public function adjust(Request $request, InventoryItem $inventory): RedirectResponse
    {
        $this->authorize('inventory.manage');

        $data = $request->validate([
            'qty'   => 'required|numeric|not_in:0',
            'notes' => 'required|string|max:500',
        ]);

        $this->svc->adjust($inventory, (float) $data['qty'], $data['notes'], auth()->user()->branch_id);

        return back()->with('success', 'Đã điều chỉnh tồn kho.');
    }

    // ─── Định mức vật tư dịch vụ ─────────────────────────────────────────────

    public function templates(): Response
    {
        $this->authorize('inventory.manage');

        $templates = InventoryServiceTemplate::with(['service', 'serviceStep', 'inventoryItem'])
            ->where('is_active', true)
            ->orderBy('service_id')
            ->get()
            ->map(fn ($t) => [
                'id'                => $t->id,
                'service_name'      => $t->service?->name,
                'step_name'         => $t->serviceStep?->step_name ?? 'Toàn dịch vụ',
                'item_name'         => $t->inventoryItem?->name,
                'item_unit'         => $t->inventoryItem?->unit,
                'qty_per_execution' => $t->qty_per_execution,
            ]);

        $services = DentalService::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $items    = InventoryItem::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit']);

        return Inertia::render('Inventory/Templates', [
            'templates' => $templates,
            'services'  => $services,
            'items'     => $items,
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $this->authorize('inventory.manage');

        $data = $request->validate([
            'service_id'        => 'required|exists:dental_services,id',
            'service_step_id'   => 'nullable|exists:dental_service_steps,id',
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'qty_per_execution' => 'required|numeric|min:0.001',
            'notes'             => 'nullable|string|max:255',
        ]);

        InventoryServiceTemplate::updateOrCreate(
            [
                'service_id'        => $data['service_id'],
                'service_step_id'   => $data['service_step_id'] ?? null,
                'inventory_item_id' => $data['inventory_item_id'],
            ],
            ['qty_per_execution' => $data['qty_per_execution'], 'notes' => $data['notes'] ?? null, 'is_active' => true]
        );

        return back()->with('success', 'Đã lưu định mức.');
    }

    public function destroyTemplate(InventoryServiceTemplate $template): RedirectResponse
    {
        $this->authorize('inventory.manage');
        $template->update(['is_active' => false]);
        return back()->with('success', 'Đã xóa định mức.');
    }
}
