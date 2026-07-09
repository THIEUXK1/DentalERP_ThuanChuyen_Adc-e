<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\DentalService;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DentalServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('services.view');

        $query = DentalService::with('category')
            ->when($request->search, fn ($q, $v) => $q->where('name', 'ilike', "%{$v}%")->orWhere('code', 'ilike', "%{$v}%"))
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->orderByDesc('id');

        $categories = ServiceCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Catalog/Services/Index', [
            'services' => $query->paginate(20)->through(fn ($s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'category' => $s->category?->name,
                'category_id' => $s->category_id,
                'cost_price' => $s->cost_price,
                'selling_price' => $s->selling_price,
                'duration_minutes' => $s->duration_minutes,
                'is_active' => $s->is_active,
            ]),
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('services.manage');

        return Inertia::render('Catalog/Services/Form', [
            'categories' => ServiceCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('services.manage');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:service_categories,id',
            'cost_price' => 'required|integer|min:0',
            'selling_price' => 'required|integer|min:0',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'is_active' => 'boolean',
        ]);

        DentalService::create([...$data, 'code' => DentalService::generateCode()]);

        return redirect()->route('catalog.services.index')->with('success', 'Đã tạo dịch vụ.');
    }

    public function edit(DentalService $service): Response
    {
        $this->authorize('services.manage');

        return Inertia::render('Catalog/Services/Form', [
            'service' => [
                'id' => $service->id,
                'code' => $service->code,
                'name' => $service->name,
                'category_id' => $service->category_id,
                'cost_price' => $service->cost_price,
                'selling_price' => $service->selling_price,
                'duration_minutes' => $service->duration_minutes,
                'is_active' => $service->is_active,
            ],
            'categories' => ServiceCategory::where('is_active', true)->orWhere('id', $service->category_id)
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, DentalService $service): RedirectResponse
    {
        $this->authorize('services.manage');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:service_categories,id',
            'cost_price' => 'required|integer|min:0',
            'selling_price' => 'required|integer|min:0',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'is_active' => 'boolean',
        ]);

        $service->update($data);

        return redirect()->route('catalog.services.index')->with('success', 'Đã cập nhật dịch vụ.');
    }

    public function destroy(DentalService $service): RedirectResponse
    {
        $this->authorize('services.manage');
        $service->delete();

        return redirect()->route('catalog.services.index')->with('success', 'Đã xóa dịch vụ.');
    }
}
