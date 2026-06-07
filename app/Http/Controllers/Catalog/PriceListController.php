<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\DentalService;
use App\Models\PriceList;
use App\Models\PriceListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PriceListController extends Controller
{
    public function index(): Response
    {
        $this->authorize('services.view');

        return Inertia::render('Catalog/PriceLists/Index', [
            'priceLists' => PriceList::withCount('items')->orderByDesc('id')->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'is_active' => $p->is_active,
                    'items_count' => $p->items_count,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('services.manage');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        PriceList::create([...$data, 'code' => PriceList::generateCode()]);

        return redirect()->route('catalog.price-lists.index')->with('success', 'Đã tạo bảng giá.');
    }

    public function show(PriceList $priceList): Response
    {
        $this->authorize('services.view');

        $existingIds = $priceList->items()->pluck('service_id');

        return Inertia::render('Catalog/PriceLists/Show', [
            'priceList' => [
                'id' => $priceList->id,
                'code' => $priceList->code,
                'name' => $priceList->name,
                'is_active' => $priceList->is_active,
            ],
            'items' => $priceList->items()->with('service')->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'service_name' => $i->service->name,
                    'service_code' => $i->service->code,
                    'unit_price' => $i->unit_price,
                ]),
            'services' => DentalService::where('is_active', true)
                ->whereNotIn('id', $existingIds)->orderBy('name')->get()
                ->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'name' => $s->name, 'selling_price' => $s->selling_price]),
        ]);
    }

    public function update(Request $request, PriceList $priceList): RedirectResponse
    {
        $this->authorize('services.manage');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $priceList->update($data);

        return back()->with('success', 'Đã cập nhật bảng giá.');
    }

    public function destroy(PriceList $priceList): RedirectResponse
    {
        $this->authorize('services.manage');
        $priceList->delete();

        return redirect()->route('catalog.price-lists.index')->with('success', 'Đã xóa bảng giá.');
    }

    public function addItem(Request $request, PriceList $priceList): RedirectResponse
    {
        $this->authorize('services.manage');

        $data = $request->validate([
            'service_id' => 'required|exists:dental_services,id',
            'unit_price' => 'required|integer|min:0',
        ]);

        PriceListItem::updateOrCreate(
            ['price_list_id' => $priceList->id, 'service_id' => $data['service_id']],
            ['unit_price' => $data['unit_price']]
        );

        return back()->with('success', 'Đã thêm dịch vụ vào bảng giá.');
    }

    public function removeItem(PriceListItem $item): RedirectResponse
    {
        $this->authorize('services.manage');
        $item->delete();

        return back()->with('success', 'Đã xóa dịch vụ khỏi bảng giá.');
    }
}
