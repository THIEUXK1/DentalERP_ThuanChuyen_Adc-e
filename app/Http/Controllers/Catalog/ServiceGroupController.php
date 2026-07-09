<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\ServiceGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceGroupController extends Controller
{
    public function index(): Response
    {
        $this->authorize('services.manage');

        $groups = ServiceGroup::withCount('categories')->orderBy('name')->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'is_active' => $g->is_active,
                'categories_count' => $g->categories_count,
            ]);

        return Inertia::render('Catalog/ServiceGroups/Index', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('services.manage');

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:service_groups,name',
        ]);

        ServiceGroup::create($data);

        return back()->with('success', 'Đã thêm nhóm dịch vụ.');
    }

    public function update(Request $request, ServiceGroup $serviceGroup): RedirectResponse
    {
        $this->authorize('services.manage');

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:service_groups,name,'.$serviceGroup->id,
            'is_active' => 'boolean',
        ]);

        $serviceGroup->update($data);

        return back()->with('success', 'Đã cập nhật nhóm dịch vụ.');
    }

    public function destroy(ServiceGroup $serviceGroup): RedirectResponse
    {
        $this->authorize('services.manage');

        if ($serviceGroup->categories()->exists()) {
            return back()->with('error', 'Không thể xóa: vẫn còn loại dịch vụ thuộc nhóm này.');
        }

        $serviceGroup->delete();

        return back()->with('success', 'Đã xóa nhóm dịch vụ.');
    }
}
