<template>
    <AppLayout title="Lịch làm việc - Báo cáo">
        <!-- ── Screen UI ──────────────────────────────────────────────── -->
        <div class="print:hidden space-y-4">

            <!-- Header -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Báo cáo lịch hẹn</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ isRange ? `${fmtDate(filters.from)} – ${fmtDate(filters.to)}` : fmtDate(filters.from) }}
                        · <strong class="text-gray-700">{{ total }}</strong> lịch hẹn
                    </p>
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
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap items-end gap-3">
                    <!-- Date range toggle -->
                    <div class="flex items-center gap-2 mr-2">
                        <label class="text-xs font-medium text-gray-500">Chọn ngày</label>
                        <button @click="useRange = false"
                            :class="['px-3 py-1.5 text-xs rounded-lg border font-medium transition-colors',
                                !useRange ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50']">
                            1 ngày
                        </button>
                        <button @click="useRange = true"
                            :class="['px-3 py-1.5 text-xs rounded-lg border font-medium transition-colors',
                                useRange ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50']">
                            Khoảng ngày
                        </button>
                    </div>

                    <div class="flex items-end gap-2 flex-wrap">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ useRange ? 'Từ ngày' : 'Ngày' }}</label>
                            <input type="date" v-model="f.from"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>
                        <template v-if="useRange">
                            <span class="text-gray-400 mb-2">→</span>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Đến ngày</label>
                                <input type="date" v-model="f.to" :min="f.from"
                                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                        </template>
                    </div>

                    <!-- Quick ranges (only when useRange) -->
                    <div v-if="useRange" class="flex items-center gap-1.5 flex-wrap">
                        <button v-for="q in quickRanges" :key="q.label" @click="applyQuick(q)"
                            class="px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors text-gray-600">
                            {{ q.label }}
                        </button>
                    </div>

                    <div class="w-px h-8 bg-gray-200 hidden sm:block self-center"></div>

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

                    <div class="flex gap-2 mt-auto">
                        <button @click="applyFilters"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 font-medium">
                            Lọc
                        </button>
                        <button @click="resetFilters" class="px-3 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50">
                            Đặt lại
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="total === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400">
                Không có lịch hẹn nào trong khoảng thời gian đã chọn
            </div>

            <!-- ── CHẾ ĐỘ 1 NGÀY: nhóm theo bác sĩ ─────────────────────── -->
            <template v-else-if="!isRange && byDoctor">
                <div v-for="group in byDoctor" :key="group.name" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="font-semibold text-indigo-900">{{ group.name }}</span>
                        </div>
                        <span class="text-xs font-medium bg-indigo-100 text-indigo-700 px-2.5 py-0.5 rounded-full">{{ group.total }} lịch hẹn</span>
                    </div>
                    <AppointmentTable :rows="group.rows" :status-badge="statusBadge" />
                </div>
            </template>

            <!-- ── CHẾ ĐỘ KHOẢNG NGÀY: nhóm theo ngày → bác sĩ ────────── -->
            <template v-else-if="isRange && byDate">
                <div v-for="dayGroup in byDate" :key="dayGroup.date" class="space-y-2">
                    <!-- Date header -->
                    <div class="flex items-center gap-3">
                        <div class="bg-indigo-600 text-white rounded-xl px-4 py-2 flex items-center gap-2 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="font-bold">{{ fmtDateFull(dayGroup.date) }}</span>
                        </div>
                        <span class="text-sm text-gray-500">{{ dayGroup.total }} lịch hẹn</span>
                    </div>

                    <!-- Doctors within this day -->
                    <div v-for="group in dayGroup.byDoctor" :key="group.name"
                        class="bg-white rounded-xl border border-gray-200 overflow-hidden ml-2">
                        <div class="px-5 py-2.5 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="font-medium text-gray-700 text-sm">{{ group.name }}</span>
                            </div>
                            <span class="text-xs text-gray-400">{{ group.total }} lịch</span>
                        </div>
                        <AppointmentTable :rows="group.rows" :status-badge="statusBadge" compact />
                    </div>
                </div>
            </template>

        </div><!-- end print:hidden -->

        <!-- ══ PRINT LAYOUT ══════════════════════════════════════════════ -->
        <div class="hidden print:block print-area">
            <div class="print-header">
                <h1>BÁO CÁO LỊCH HẸN</h1>
                <p v-if="isRange">Từ ngày {{ fmtDate(filters.from) }} đến {{ fmtDate(filters.to) }}</p>
                <p v-else>Ngày: {{ fmtDate(filters.from) }}</p>
                <p class="text-sm">Xuất lúc {{ printTime }} · Tổng {{ total }} lịch hẹn</p>
            </div>

            <!-- Single day print -->
            <template v-if="!isRange && byDoctor">
                <div v-for="group in byDoctor" :key="group.name" class="print-group">
                    <div class="print-doctor-header">
                        Bác sĩ: {{ group.name }}
                        <span class="print-total">({{ group.total }} lịch hẹn)</span>
                    </div>
                    <PrintTable :rows="group.rows" />
                </div>
            </template>

            <!-- Range print -->
            <template v-else-if="isRange && byDate">
                <div v-for="dayGroup in byDate" :key="dayGroup.date" class="print-date-section">
                    <div class="print-date-header">{{ fmtDateFull(dayGroup.date) }} ({{ dayGroup.total }} lịch hẹn)</div>
                    <div v-for="group in dayGroup.byDoctor" :key="group.name" class="print-group">
                        <div class="print-doctor-header sub">
                            Bác sĩ: {{ group.name }}
                            <span class="print-total">({{ group.total }} lịch)</span>
                        </div>
                        <PrintTable :rows="group.rows" />
                    </div>
                </div>
            </template>

            <div class="print-footer">
                <span>Tổng cộng: {{ total }} lịch hẹn</span>
                <span>DentalERP</span>
            </div>
        </div>
    </AppLayout>
</template>

<!-- ── Shared table component (screen) ─────────────────────────── -->
<script>
const AppointmentTable = {
    props: { rows: Array, statusBadge: Function, compact: Boolean },
    template: `
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/60">
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 w-20">Giờ</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500">Bệnh nhân</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 hidden md:table-cell">SĐT</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 hidden lg:table-cell">Dịch vụ</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 hidden lg:table-cell">Ghế</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500">Trạng thái</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 hidden xl:table-cell">Ghi chú</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <tr v-for="(row, idx) in rows" :key="row.id"
                    :class="['hover:bg-gray-50/70 transition-colors', idx % 2 !== 0 ? 'bg-gray-50/30' : '']">
                    <td class="px-4 py-2.5">
                        <div class="font-bold text-gray-900 font-mono text-sm">{{ row.scheduled_at }}</div>
                        <div class="text-xs text-gray-400">→ {{ row.ends_at }}</div>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="font-semibold text-gray-900">{{ row.patient }}</div>
                        <div class="text-xs text-gray-400 font-mono">{{ row.code }}</div>
                    </td>
                    <td class="px-4 py-2.5 text-gray-600 hidden md:table-cell">{{ row.patient_phone || '—' }}</td>
                    <td class="px-4 py-2.5 text-gray-600 hidden lg:table-cell text-sm">{{ row.service }}</td>
                    <td class="px-4 py-2.5 text-gray-500 text-xs hidden lg:table-cell">{{ row.chair }}</td>
                    <td class="px-4 py-2.5">
                        <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold', statusBadge(row.status)]">
                            {{ row.status_label }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-500 hidden xl:table-cell max-w-xs">
                        <span v-if="row.notes" class="block truncate">{{ row.notes }}</span>
                        <span v-else class="text-gray-300">—</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    `,
};

const PrintTable = {
    props: { rows: Array },
    template: `
    <table class="print-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Giờ</th>
                <th>Bệnh nhân</th>
                <th>SĐT</th>
                <th>Dịch vụ</th>
                <th>Ghế</th>
                <th>TL (ph)</th>
                <th>Trạng thái</th>
                <th>Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(row, idx) in rows" :key="row.id">
                <td class="center">{{ idx + 1 }}</td>
                <td class="center mono">{{ row.scheduled_at }}→{{ row.ends_at }}</td>
                <td><strong>{{ row.patient }}</strong><br><span class="small gray">{{ row.code }}</span></td>
                <td class="mono small">{{ row.patient_phone || '—' }}</td>
                <td class="small">{{ row.service }}</td>
                <td class="center small">{{ row.chair }}</td>
                <td class="center">{{ row.duration_minutes }}</td>
                <td class="center"><strong>{{ row.status_label }}</strong></td>
                <td class="small">{{ row.notes || '—' }}</td>
            </tr>
        </tbody>
    </table>
    `,
};
</script>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    byDoctor: { type: Array, default: null },
    byDate:   { type: Array, default: null },
    isRange:  Boolean,
    total:    Number,
    branches: Array,
    doctors:  Array,
    statuses: Array,
    filters:  Object,
});

// ── Filter state ────────────────────────────────────────────────
const useRange = ref(props.isRange);
const f = ref({
    from:      props.filters.from      ?? dayjs().format('YYYY-MM-DD'),
    to:        props.filters.to        ?? dayjs().format('YYYY-MM-DD'),
    branch_id: props.filters.branchId  ?? '',
    doctor_id: props.filters.doctorId  ?? '',
    status:    props.filters.status    ?? '',
});

// Quick range presets
const quickRanges = [
    { label: 'Hôm nay',   from: dayjs().format('YYYY-MM-DD'),                        to: dayjs().format('YYYY-MM-DD') },
    { label: 'Hôm qua',   from: dayjs().subtract(1,'day').format('YYYY-MM-DD'),      to: dayjs().subtract(1,'day').format('YYYY-MM-DD') },
    { label: '7 ngày qua',from: dayjs().subtract(6,'day').format('YYYY-MM-DD'),      to: dayjs().format('YYYY-MM-DD') },
    { label: 'Tuần này',  from: dayjs().startOf('week').add(1,'day').format('YYYY-MM-DD'), to: dayjs().endOf('week').add(1,'day').format('YYYY-MM-DD') },
    { label: 'Tháng này', from: dayjs().startOf('month').format('YYYY-MM-DD'),       to: dayjs().endOf('month').format('YYYY-MM-DD') },
    { label: 'Tháng trước', from: dayjs().subtract(1,'month').startOf('month').format('YYYY-MM-DD'), to: dayjs().subtract(1,'month').endOf('month').format('YYYY-MM-DD') },
];

function applyQuick(q) { f.value.from = q.from; f.value.to = q.to; }

function applyFilters() {
    const params = {
        from:      f.value.from,
        to:        useRange.value ? f.value.to : f.value.from,
        branch_id: f.value.branch_id || undefined,
        doctor_id: f.value.doctor_id || undefined,
        status:    f.value.status    || undefined,
    };
    router.get(route('reports.daily-schedule'), params, { preserveScroll: true });
}

function resetFilters() {
    const today = dayjs().format('YYYY-MM-DD');
    f.value = { from: today, to: today, branch_id: '', doctor_id: '', status: '' };
    useRange.value = false;
    applyFilters();
}

// ── Helpers ─────────────────────────────────────────────────────
function fmtDate(d)     { return d ? dayjs(d).format('DD/MM/YYYY') : ''; }
function fmtDateFull(d) {
    const days = ['Chủ nhật','Thứ Hai','Thứ Ba','Thứ Tư','Thứ Năm','Thứ Sáu','Thứ Bảy'];
    const dj = dayjs(d);
    return `${days[dj.day()]}, ${dj.format('DD/MM/YYYY')}`;
}
const printTime = computed(() => dayjs().format('HH:mm DD/MM/YYYY'));

// ── Status badge ─────────────────────────────────────────────────
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

// ── Actions ──────────────────────────────────────────────────────
function printPage() { window.print(); }

function exportCsv() {
    const BOM = '﻿';
    const headers = ['Ngày','Bác sĩ','Mã lịch','Giờ bắt đầu','Giờ kết thúc','TL (phút)','Bệnh nhân','SĐT','Dịch vụ','Ghế','Trạng thái','Ghi chú'];
    const rows = [];

    const pushRows = (doctor, aptRows, dateLabel = '') => {
        for (const row of aptRows) {
            rows.push([
                dateLabel || fmtDate(props.filters.from),
                doctor,
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
    };

    if (!props.isRange && props.byDoctor) {
        for (const g of props.byDoctor) pushRows(g.name, g.rows);
    } else if (props.isRange && props.byDate) {
        for (const dayGroup of props.byDate) {
            for (const g of dayGroup.byDoctor) pushRows(g.name, g.rows, dayGroup.date_label);
        }
    }

    const from = props.filters.from, to = props.filters.to;
    const fname = from === to ? `lich-hen-${from}.csv` : `lich-hen-${from}-den-${to}.csv`;
    const csv  = BOM + [headers.join(','), ...rows].join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = fname; a.click();
    URL.revokeObjectURL(url);
}
</script>

<style>
@media print {
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { font-family: Arial, sans-serif; font-size: 11pt; color: #111; }

    .print-area { width: 100%; }

    .print-header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #333; }
    .print-header h1 { font-size: 18pt; font-weight: bold; margin: 0 0 6px; }
    .print-header p  { margin: 2px 0; font-size: 11pt; color: #444; }
    .print-header .text-sm { font-size: 9pt; color: #888; }

    .print-date-section { margin-bottom: 28px; }
    .print-date-header  { font-size: 13pt; font-weight: bold; color: #1e3a8a; margin-bottom: 8px; padding: 5px 0; border-bottom: 2px solid #bfdbfe; }
    .print-group        { margin-bottom: 18px; page-break-inside: avoid; }

    .print-doctor-header { background: #1e3a8a; color: #fff; font-weight: bold; font-size: 12pt; padding: 7px 12px; border-radius: 4px 4px 0 0; }
    .print-doctor-header.sub { background: #4f46e5; font-size: 11pt; padding: 5px 12px; }
    .print-total { font-size: 9.5pt; font-weight: normal; margin-left: 12px; opacity: .85; }

    .print-table { width: 100%; border-collapse: collapse; font-size: 10pt; }
    .print-table th { background: #eff6ff; border: 1px solid #bfdbfe; padding: 5px 7px; font-size: 9pt; font-weight: bold; text-align: left; white-space: nowrap; }
    .print-table td { border: 1px solid #e5e7eb; padding: 4px 7px; vertical-align: top; }
    .print-table tr:nth-child(even) td { background: #f9fafb; }
    .print-table .center { text-align: center; }
    .print-table .mono   { font-family: monospace; font-size: 9pt; }
    .print-table .small  { font-size: 9pt; }
    .print-table .gray   { color: #6b7280; }

    .print-footer { display: flex; justify-content: space-between; margin-top: 16px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 9pt; color: #666; }
}
</style>
