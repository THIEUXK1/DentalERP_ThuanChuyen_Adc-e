<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DentalChair;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DentalChairController extends Controller
{
    public function index(): Response
    {
        $this->authorize('branches.manage');

        return Inertia::render('Core/DentalChairs/Index', [
            'chairs' => DentalChair::with('branch')->orderBy('branch_id')->orderBy('code')->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                    'branch' => $c->branch->name,
                    'branch_id' => $c->branch_id,
                    'is_active' => $c->is_active,
                ]),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('branches.manage');

        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        DentalChair::create($data);

        return redirect()->route('dental-chairs.index')->with('success', 'Đã tạo ghế nha.');
    }

    public function update(Request $request, DentalChair $dentalChair): RedirectResponse
    {
        $this->authorize('branches.manage');

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        $dentalChair->update($data);

        return redirect()->route('dental-chairs.index')->with('success', 'Đã cập nhật ghế nha.');
    }

    public function destroy(DentalChair $dentalChair): RedirectResponse
    {
        $this->authorize('branches.manage');
        $dentalChair->delete();

        return redirect()->route('dental-chairs.index')->with('success', 'Đã xóa ghế nha.');
    }
}
