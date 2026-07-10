<?php

namespace App\Http\Controllers\Dental;

use App\Enums\DentalConditionGroup;
use App\Http\Controllers\Controller;
use App\Models\DentalCondition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConditionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('dental.manage');

        $query = DentalCondition::orderBy('group')->orderBy('sort_order')->orderBy('name');

        if ($search = $request->search) {
            $query->where(fn ($q) => $q->where('name', 'ilike', "%$search%")->orWhere('code', 'ilike', "%$search%"));
        }
        if ($group = $request->group) {
            $query->where('group', $group);
        }

        return Inertia::render('Dental/Conditions/Index', [
            'conditions' => $query->get()->map(fn ($c) => [
                'id'          => $c->id,
                'code'        => $c->code,
                'name'        => $c->name,
                'group'       => $c->group->value,
                'group_label' => $c->group->label(),
                'group_color' => $c->group->color(),
                'description' => $c->description,
                'sort_order'  => $c->sort_order,
                'is_active'   => $c->is_active,
            ]),
            'groups'  => collect(DentalConditionGroup::cases())->map(fn ($g) => [
                'value' => $g->value, 'label' => $g->label(), 'color' => $g->color(),
            ]),
            'filters' => $request->only(['search', 'group']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('dental.manage');

        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'group'       => 'required|string',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        DentalCondition::createWithCode([
            ...$data,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active'  => true,
        ]);

        return back()->with('success', 'Đã thêm bệnh/vấn đề.');
    }

    public function update(Request $request, DentalCondition $condition): RedirectResponse
    {
        $this->authorize('dental.manage');

        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'group'       => 'required|string',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $condition->update($data);

        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(DentalCondition $condition): RedirectResponse
    {
        $this->authorize('dental.manage');
        $condition->delete();

        return back()->with('success', 'Đã xóa.');
    }
}
