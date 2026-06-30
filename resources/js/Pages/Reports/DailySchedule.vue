<template>
    <AppLayout title="Lịch làm việc theo ngày">
        <!-- ── Screen UI ─────────────────────────────────────── -->
        <div class="print:hidden space-y-4">

            <!-- Header -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Lịch làm việc theo ngày</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Danh sách lịch hẹn chi tiết cho từng bác sĩ</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button @click="exportCsv"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white text-sm rounded-xl hover:bg-emerald-700 shadow-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Xuất Excel (CSV)
                    </button>
                    <button @click="printPage"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm rounded-xl hover:bg-indigo-700 shadow-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        In / Xuất PDF
                    </button>
                </div>
            </div>

            <!-- Filter bar -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Ngày</label>
                    <input type="date" v-model="f.date"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Chi nhánh</label>
                    <select v-model="f.branch_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Bác sĩ</label>
                    <select v-model="f.doctor_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả</option>
                        <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Trạng thái</label>
                    <select v-model="f.status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
                <button @click="applyFilters"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 font-medium">
                    Lọc
                </button>
                <button @click="resetFilters" class="px-3 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50">
                    Đặt lại
                </button>
                <span class="ml-auto text-sm text-gray-500">Tổng: <strong class="text-gray-800">{{ total }}</strong> lịch hẹn</span>
            </div>

            <!-- Results (screen) -->
            <div v-if="byDoctor.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400">
                Không có lịch hẹn nào trong ngày {{ fmtDate(filters.date) }}
            </div>
            <div v-else class="space-y-4">
                <div v-for="group in byDoctor" :key="group.name" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <!-- Doctor header -->
                    <div class="px-5 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="font-semibold text-indigo-900">{{ group.name }}</span>
                        </div>
                        <span class="text-xs font-medium bg-indigo-100 text-indigo-700 px-2.5 py-0.5 rounded-full">
                            {{ group.total }} lịch hẹn
                        </span>
                    </div>
                    <!-- Appointments table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/60">
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 w-20">Giờ</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500">Bệnh nhân</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 hidden md:table-cell">SĐT</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 hidden lg:table-cell">Dịch vụ</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 hidden lg:table-cell">Ghế</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500">Trạng thái</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 hidden xl:table-cell">Ghi chú</th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(row, idx) in group.rows" :key="row.id"
                                    :class="['hover:bg-gray-50/70 transition-colors', idx % 2 === 0 ? '' : 'bg-gray-50/30']">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900 font-mono">{{ row.scheduled_at }}</div>
                                        <div class="text-xs text-gray-400">→ {{ row.ends_at }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">{{ row.patient }}</div>
                                        <div class="text-xs text-gray-400 font-mono">{{ row.code }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 hidden md:table-cell">{{ row.patient_phone || '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600 hidden lg:table-cell">{{ row.service }}</td>
                                    <td class="px-4 py-3 text-gray-500 text-xs hidden lg:table-cell">{{ row.chair }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold', statusBadge(row.status)]">
                                            {{ row.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500 max-w-xs hidden xl:table-cell">
                                        <span v-if="row.notes" class="truncate block">{{ row.notes }}</span>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <Link :href="route('schedule.appointments.show', row.id)"
                                            class="text-indigo-400 hover:text-indigo-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- end print:hidden -->

        <!-- ════════════════════════════════════════════════════════
             PRINT LAYOUT (chỉ hiện khi in / xuất PDF)
        ════════════════════════════════════════════════════════ -->
        <div class="hidden print:block print-area">
            <!-- Print header -->
            <div class="print-header">
                <h1>LỊCH LÀM VIỆC THEO NGÀY</h1>
                <p>Ngày: <strong>{{ fmtDate(filters.date) }}</strong>
                    <span v-if="filters.branchId || filters.doctorId || filters.status"> · Bộ lọc đang áp dụng</span>
                </p>
                <p class="text-sm">Xuất lúc: {{ printTime }}</p>
            </div>

            <div v-if="byDoctor.length === 0" style="text-align:center; color:#999; padding:32px 0">
                Không có lịch hẹn
            </div>

            <div v-for="group in byDoctor" :key="group.name" class="print-group">
                <div class="print-doctor-header">
                    Bác sĩ: {{ group.name }}
                    <span class="print-total">({{ group.total }} lịch hẹn)</span>
                </div>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Giờ</th>
                            <th>Bệnh nhân</th>
                            <th>SĐT</th>
                            <th>Dịch vụ</th>
                            <th>Ghế</th>
                            <th>Thời lượng</th>
                            <th>Trạng thái</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, idx) in group.rows" :key="row.id">
                            <td class="center">{{ idx + 1 }}</td>
                            <td class="center mono">{{ row.scheduled_at }} → {{ row.ends_at }}</td>
                            <td><strong>{{ row.patient }}</strong><br><span class="small gray">{{ row.code }}</span></td>
                            <td class="mono">{{ row.patient_phone || '—' }}</td>
                            <td>{{ row.service }}</td>
                            <td class="center">{{ row.chair }}</td>
                            <td class="center">{{ row.duration_minutes }} ph</td>
                            <td class="center"><strong>{{ row.status_label }}</strong></td>
                            <td class="small">{{ row.notes || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="print-footer">
                <span>Tổng cộng: {{ total }} lịch hẹn</span>
                <span>In bởi hệ thống DentalERP</span>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    byDoctor: Array,
    total:    Number,
    branches: Array,
    doctors:  Array,
    statuses: Array,
    filters:  Object,
});

// ── Filters ────────────────────────────────────────────────────
const f = ref({
    date:      props.filters.date      ?? dayjs().format('YYYY-MM-DD'),
    branch_id: props.filters.branchId  ?? '',
    doctor_id: props.filters.doctorId  ?? '',
    status:    props.filters.status    ?? '',
});

function applyFilters() {
    router.get(route('reports.daily-schedule'), {
        date:      f.value.date,
        branch_id: f.value.branch_id || undefined,
        doctor_id: f.value.doctor_id || undefined,
        status:    f.value.status    || undefined,
    }, { preserveScroll: true });
}
function resetFilters() {
    f.value = { date: dayjs().format('YYYY-MM-DD'), branch_id: '', doctor_id: '', status: '' };
    applyFilters();
}

// ── Helpers ────────────────────────────────────────────────────
function fmtDate(d) { return d ? dayjs(d).format('DD/MM/YYYY') : ''; }
const printTime = computed(() => dayjs().format('HH:mm DD/MM/YYYY'));

// ── Status badge ───────────────────────────────────────────────
const STATUS_BADGE = {
    pending:       'bg-gray-500 text-white',
    booked:        'bg-blue-500 text-white',
    confirmed:     'bg-indigo-600 text-white',
    rescheduled:   'bg-yellow-500 text-white',
    arrived_early: 'bg-teal-500 text-white',
    checked_in:    'bg-teal-600 text-white',
    arrived_late:  'bg-orange-500 text-white',
    no_show:       'bg-red-400 text-white',
    cancelled:     'bg-gray-400 text-white',
    in_treatment:  'bg-purple-500 text-white',
    completed:     'bg-emerald-500 text-white',
};
function statusBadge(s) { return STATUS_BADGE[s] ?? 'bg-gray-300 text-white'; }

// ── Print ──────────────────────────────────────────────────────
function printPage() { window.print(); }

// ── CSV Export ─────────────────────────────────────────────────
function exportCsv() {
    const BOM = '﻿'; // UTF-8 BOM for Excel
    const headers = ['Bác sĩ','Mã lịch','Giờ bắt đầu','Giờ kết thúc','Thời lượng (ph)','Bệnh nhân','SĐT','Dịch vụ','Ghế','Trạng thái','Ghi chú'];
    const rows = [];
    for (const group of props.byDoctor) {
        for (const row of group.rows) {
            rows.push([
                group.name,
                row.code,
                row.scheduled_at,
                row.ends_at,
                row.duration_minutes,
                row.patient,
                row.patient_phone,
                row.service,
                row.chair,
                row.status_label,
                row.notes,
            ].map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(','));
        }
    }
    const csv = BOM + [headers.join(','), ...rows].join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `lich-hen-${props.filters.date}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<style>
/* ── Print styles ─────────────────────────────────────────────── */
@media print {
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { font-family: Arial, sans-serif; font-size: 11pt; color: #111; }

    .print-area { width: 100%; }

    .print-header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #333; }
    .print-header h1 { font-size: 18pt; font-weight: bold; margin: 0 0 6px; }
    .print-header p  { margin: 2px 0; font-size: 11pt; color: #444; }
    .print-header .text-sm { font-size: 9pt; color: #888; }

    .print-group { margin-bottom: 24px; page-break-inside: avoid; }

    .print-doctor-header {
        background: #1e3a8a;
        color: #fff;
        font-weight: bold;
        font-size: 12pt;
        padding: 7px 12px;
        border-radius: 4px 4px 0 0;
    }
    .print-total { font-size: 10pt; font-weight: normal; margin-left: 12px; opacity: 0.85; }

    .print-table { width: 100%; border-collapse: collapse; font-size: 10pt; }
    .print-table th {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        padding: 5px 8px;
        font-size: 9.5pt;
        font-weight: bold;
        text-align: left;
        white-space: nowrap;
    }
    .print-table td { border: 1px solid #e5e7eb; padding: 5px 8px; vertical-align: top; }
    .print-table tr:nth-child(even) td { background: #f9fafb; }
    .print-table .center { text-align: center; }
    .print-table .mono   { font-family: monospace; }
    .print-table .small  { font-size: 9pt; }
    .print-table .gray   { color: #6b7280; }

    .print-footer {
        display: flex;
        justify-content: space-between;
        margin-top: 16px;
        padding-top: 8px;
        border-top: 1px solid #ccc;
        font-size: 9pt;
        color: #666;
    }
}
</style>
