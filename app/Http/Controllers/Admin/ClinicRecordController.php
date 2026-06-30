<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClinicRecordController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ClinicRecord::query()
            ->when($request->search, function ($q, $v) {
                $q->where(function ($q2) use ($v) {
                    $q2->where('patient_name', 'like', "%{$v}%")
                       ->orWhere('patient_code', 'like', "%{$v}%")
                       ->orWhere('service_name', 'like', "%{$v}%")
                       ->orWhere('doctor_name', 'like', "%{$v}%")
                       ->orWhere('phone', 'like', "%{$v}%");
                });
            })
            ->when($request->record_type, fn ($q, $v) => $q->where('record_type', $v))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('record_date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('record_date', '<=', $v))
            ->orderByDesc('record_date')
            ->orderByDesc('id');

        $perPage = in_array((int) $request->per_page, [20, 50, 100]) ? (int) $request->per_page : 50;

        return Inertia::render('Admin/ClinicRecords/Index', [
            'records' => $query->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'record_type', 'date_from', 'date_to', 'per_page']),
            'record_types' => ClinicRecord::distinct()->pluck('record_type')->filter()->values(),
        ]);
    }
}