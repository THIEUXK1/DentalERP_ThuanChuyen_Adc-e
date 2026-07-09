<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Models\ServiceGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('services.manage');

        $categories = ServiceCategory::withCount('services')->with('group:id,name')->orderBy('name')->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'group_id' => $c->group_id,
                'group' => $c->group?->name,
                'is_active' => $c->is_active,
                'services_count' => $c->services_count,
            ]);

        $groups = ServiceGroup::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Catalog/ServiceCategories/Index', compact('categories', 'groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('services.manage');

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:service_categories,name',
            'group_id' => 'nullable|exists:service_groups,id',
        ]);

        ServiceCategory::create($data);

        return back()->with('success', 'Đã thêm loại dịch vụ.');
    }

    public function update(Request $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $this->authorize('services.manage');

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:service_categories,name,'.$serviceCategory->id,
            'group_id' => 'nullable|exists:service_groups,id',
            'is_active' => 'boolean',
        ]);

        $serviceCategory->update($data);

        return back()->with('success', 'Đã cập nhật loại dịch vụ.');
    }

    public function destroy(ServiceCategory $serviceCategory): RedirectResponse
    {
        $this->authorize('services.manage');

        if ($serviceCategory->services()->exists()) {
            return back()->with('error', 'Không thể xóa: vẫn còn dịch vụ thuộc loại này.');
        }

        $serviceCategory->delete();

        return back()->with('success', 'Đã xóa loại dịch vụ.');
    }
}
