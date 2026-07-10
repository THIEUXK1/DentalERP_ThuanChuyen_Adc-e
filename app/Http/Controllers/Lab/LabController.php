<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Models\LabPriceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('labo.view');

        $query = Lab::withCount('orders')
            ->when($request->search, fn ($q, $v) => $q->where('name', 'ilike', "%{$v}%")->orWhere('code', 'ilike', "%{$v}%"))
            ->when(isset($request->is_active), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderByDesc('id');

        return Inertia::render('Lab/Labs/Index', [
            'labs'    => $query->paginate(20)->through(fn ($l) => [
                'id'           => $l->id,
                'code'         => $l->code,
                'name'         => $l->name,
                'phone'        => $l->phone,
                'contact_person' => $l->contact_person,
                'is_active'    => $l->is_active,
                'orders_count' => $l->orders_count,
            ]),
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('labo.manage');
        return Inertia::render('Lab/Labs/Form', ['lab' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('labo.manage');

        $data = $this->validated($request);
        $lab  = Lab::createWithCode($data);

        return redirect()->route('lab.labs.show', $lab)->with('success', 'Đã tạo labo.');
    }

    public function show(Lab $lab): Response
    {
        $this->authorize('labo.view');

        $lab->load('priceItems');

        return Inertia::render('Lab/Labs/Form', [
            'lab' => [
                'id'             => $lab->id,
                'code'           => $lab->code,
                'name'           => $lab->name,
                'phone'          => $lab->phone,
                'email'          => $lab->email,
                'address'        => $lab->address,
                'contact_person' => $lab->contact_person,
                'bank_account'   => $lab->bank_account,
                'notes'          => $lab->notes,
                'is_active'      => $lab->is_active,
                'price_items'    => $lab->priceItems->map(fn ($p) => [
                    'id'           => $p->id,
                    'service_name' => $p->service_name,
                    'unit_price'   => $p->unit_price,
                    'notes'        => $p->notes,
                ])->values()->all(),
            ],
        ]);
    }

    public function update(Request $request, Lab $lab): RedirectResponse
    {
        $this->authorize('labo.manage');

        $lab->update($this->validated($request));

        return back()->with('success', 'Đã cập nhật labo.');
    }

    public function destroy(Lab $lab): RedirectResponse
    {
        $this->authorize('labo.manage');

        if ($lab->orders()->exists()) {
            return back()->with('error', 'Không thể xóa labo đã có đơn đặt.');
        }

        $lab->delete();

        return redirect()->route('lab.labs.index')->with('success', 'Đã xóa labo.');
    }

    public function storePriceItem(Request $request, Lab $lab): RedirectResponse
    {
        $this->authorize('labo.manage');

        $data = $request->validate([
            'service_name' => 'required|string|max:255',
            'unit_price'   => 'required|integer|min:0',
            'notes'        => 'nullable|string|max:255',
        ]);

        $lab->priceItems()->create($data);

        return back()->with('success', 'Đã thêm giá dịch vụ.');
    }

    public function destroyPriceItem(Lab $lab, LabPriceItem $priceItem): RedirectResponse
    {
        $this->authorize('labo.manage');
        $priceItem->delete();

        return back()->with('success', 'Đã xóa.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'bank_account'   => 'nullable|string|max:100',
            'notes'          => 'nullable|string',
            'is_active'      => 'boolean',
        ]);
    }
}
