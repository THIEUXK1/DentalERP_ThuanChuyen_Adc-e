<?php

namespace App\Http\Controllers;

use App\Exports\TableExport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Turns the rows a list screen is currently displaying into a styled workbook.
 *
 * List pages such as Cashier/Invoices and Clinical/TreatmentPlans fetch their data once
 * and then filter/sort/search entirely client-side; re-implementing every one of those
 * filters here would drift out of sync. Instead the page posts what it is showing plus a
 * column spec, and this endpoint only formats it. The payload is the user's own data —
 * nothing is read from the database here — and TableExport writes every cell explicitly,
 * so no client string can land in the sheet as a formula.
 */
class TableExportController extends Controller
{
    private const MAX_ROWS = 20000;

    public function table(Request $request): BinaryFileResponse
    {
        $data = $request->validate([
            'heading' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'meta' => ['nullable', 'string', 'max:300'],
            'sheet_title' => ['nullable', 'string', 'max:31'],
            'accent' => ['nullable', 'string', 'regex:/^FF[0-9A-F]{6}$/'],
            'filename' => ['nullable', 'string', 'max:80'],

            'columns' => ['required', 'array', 'min:1', 'max:40'],
            'columns.*.key' => ['required', 'string', 'max:60'],
            'columns.*.label' => ['required', 'string', 'max:60'],
            'columns.*.type' => ['nullable', 'string', 'in:text,money,number,date,status,code'],
            'columns.*.width' => ['nullable', 'numeric', 'min:3', 'max:80'],
            'columns.*.total' => ['nullable', 'boolean'],
            'columns.*.bold' => ['nullable', 'boolean'],
            'columns.*.colors' => ['nullable', 'array', 'max:40'],

            'rows' => ['present', 'array', 'max:'.self::MAX_ROWS],

            'bands' => ['nullable', 'array', 'max:8'],
            'bands.*.label' => ['required', 'string', 'max:60'],
            'bands.*.first' => ['required', 'string', 'regex:/^[A-Z]{1,2}$/'],
            'bands.*.last' => ['required', 'string', 'regex:/^[A-Z]{1,2}$/'],
            'bands.*.color' => ['nullable', 'string', 'regex:/^FF[0-9A-F]{6}$/'],
        ]);

        $export = new TableExport(
            heading: $data['heading'],
            subtitle: $data['subtitle'] ?? '',
            meta: trim(($data['meta'] ?? '').'    |    Xuất lúc '.now()->format('H:i d/m/Y'), ' |'),
            columns: $data['columns'],
            rows: array_map(fn ($r) => is_array($r) ? $r : [], $data['rows']),
            bands: $data['bands'] ?? [],
            sheetTitle: $data['sheet_title'] ?? 'Dữ liệu',
            accentColor: $data['accent'] ?? 'FF312E81',
        );

        $name = Str::slug($data['filename'] ?? $data['heading']) ?: 'bao-cao';

        return Excel::download($export, "{$name}_".now()->format('Y-m-d').'.xlsx');
    }
}
