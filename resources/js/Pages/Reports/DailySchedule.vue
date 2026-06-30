<template>
    <AppLayout title="Báo cáo lịch hẹn">

        <!-- ── Screen ──────────────────────────────────────────────────── -->
        <div class="print:hidden space-y-4">

            <!-- Header -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Báo cáo lịch hẹn</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ rangeLabel }}
                        · <strong class="text-gray-800">{{ totalFiltered }}</strong> lịch hẹn
                        <span v-if="totalFiltered !== appointments.length" class="text-gray-400">(trong {{ appointments.length }} đã tải)</span>
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button @click="exportCsv"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white text-sm rounded-xl hover:bg-emerald-700 shadow-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Xuất CSV
                    </button>
                    <button @click="printPage"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm rounded-xl hover:bg-indigo-700 shadow-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        In / PDF
                    </button>
                </div>
            </div>

            <!-- ── Filter bar ──────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">

                <!-- Row 1: date range -->
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm flex-shrink-0">
                        <button @click="useRange = false"
                            :class="['px-3 py-2 font-medium transition-colors', !useRange ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50']">
                            1 ngày
                        </button>
                        <button @click="useRange = true"
                            :class="['px-3 py-2 font-medium border-l border-gray-300 transition-colors', useRange ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50']">
                            Khoảng ngày
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ useRange ? 'Từ ngày' : 'Ngày' }}</label>
                        <input type="date" v-model="f.from"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>
                    <template v-if="useRange">
                        <span class="text-gray-400 self-end mb-2">→</span>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Đến ngày</label>
                            <input type="date" v-model="f.to" :min="f.from"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>
                    </template>

                    <!-- Quick range pills -->
                    <div v-if="useRange" class="flex flex-wrap gap-1.5 self-end pb-0.5">
                        <button v-for="q in quickRanges" :key="q.label"
                            @click="f.from = q.from; f.to = q.to"
                            class="px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors text-gray-600 font-medium">
                            {{ q.label }}
                        </button>
                    </div>

                    <button @click="reloadRange"
                        class="self-end px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700 font-medium flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Tải dữ liệu
                    </button>
                </div>

                <!-- Row 2: quick filters -->
                <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-100">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Lọc nhanh:</span>

                    <div class="flex-1 min-w-[180px]">
                        <div class="relative">
                            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                            </svg>
                            <input v-model="f.search" type="text" placeholder="Tìm tên bệnh nhân, SĐT, mã..."
                                class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>
                    </div>

                    <select v-model="f.branch_id"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả chi nhánh</option>
                        <option v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                    </select>

                    <select v-model="f.doctor_id"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả bác sĩ</option>
                        <option v-for="d in doctors" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                    </select>

                    <select v-model="f.status"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả trạng thái</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>

                    <button v-if="hasQuickFilters" @click="clearQuick"
                        class="text-xs text-red-500 hover:text-red-700 flex items-center gap-1 font-medium border border-red-200 rounded-lg px-2.5 py-2 hover:bg-red-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Xóa lọc
                    </button>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="totalFiltered === 0"
                class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 text-sm">
                Không có lịch hẹn nào phù hợp với bộ lọc.
            </div>

            <!-- ── 1 ngày: nhóm theo bác sĩ ───────────────────────────── -->
            <template v-else-if="!useRange">
                <div v-for="group in groupedResult" :key="group.name"
                    class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <!-- Doctor header -->
                    <div class="px-5 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="font-semibold text-indigo-900">{{ group.name }}</span>
                        </div>
                        <span class="text-xs font-medium bg-indigo-100 text-indigo-700 px-2.5 py-0.5 rounded-full">
                            {{ group.rows.length }} lịch hẹn
                        </span>
                    </div>
                    <!-- Appointment table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50">
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 w-28">Giờ</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500">Bệnh nhân</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500">SĐT</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500">Dịch vụ</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500">Ghế</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500">Trạng thái</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500">Ghi chú</th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(row, idx) in group.rows" :key="row.id"
                                    :class="['hover:bg-indigo-50/30 transition-colors', idx % 2 !== 0 ? 'bg-gray-50/40' : '']">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900 font-mono">{{ row.scheduled_at }}</div>
                                        <div class="text-xs text-gray-400">→ {{ row.ends_at }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">{{ row.patient }}</div>
                                        <div class="text-xs text-gray-400 font-mono">{{ row.code }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ row.patient_phone || '—' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ row.service }}</td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ row.chair }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="['inline-flex px-2.5 py-1 rounded-full text-xs font-bold', statusBadge(row.status)]">
                                            {{ row.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500 max-w-xs">
                                        <span v-if="row.notes" class="block truncate max-w-[200px]" :title="row.notes">{{ row.notes }}</span>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <a :href="'/schedule/appointments/' + row.id"
                                            class="inline-flex text-gray-300 hover:text-indigo-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <!-- ── Khoảng ngày: nhóm theo ngày → bác sĩ ──────────────── -->
            <template v-else>
                <div v-for="dayGroup in groupedResult" :key="dayGroup.date" class="space-y-2">
                    <!-- Date header -->
                    <div class="flex items-center gap-3">
                        <div class="bg-indigo-600 text-white rounded-xl px-4 py-2 flex items-center gap-2 shadow-sm">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="font-bold">{{ fmtDateFull(dayGroup.date) }}</span>
                        </div>
                        <span class="text-sm text-gray-500 font-medium">{{ dayGroup.total }} lịch hẹn</span>
                    </div>

                    <!-- Per-doctor tables -->
                    <div v-for="group in dayGroup.byDoctor" :key="group.name"
                        class="bg-white rounded-xl border border-gray-200 overflow-hidden ml-2">
                        <div class="px-5 py-2.5 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                            <span class="font-medium text-gray-700 text-sm">{{ group.name }}</span>
                            <span class="text-xs text-gray-400">{{ group.rows.length }} lịch</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50/50">
                                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 w-28">Giờ</th>
                                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500">Bệnh nhân</th>
                                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500">SĐT</th>
                                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500">Dịch vụ</th>
                                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500">Ghế</th>
                                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500">Trạng thái</th>
                                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500">Ghi chú</th>
                                        <th class="w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="(row, idx) in group.rows" :key="row.id"
                                        :class="['hover:bg-indigo-50/30 transition-colors', idx % 2 !== 0 ? 'bg-gray-50/40' : '']">
                                        <td class="px-4 py-2.5">
                                            <div class="font-bold text-gray-900 font-mono text-sm">{{ row.scheduled_at }}</div>
                                            <div class="text-xs text-gray-400">→ {{ row.ends_at }}</div>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="font-semibold text-gray-900">{{ row.patient }}</div>
                                            <div class="text-xs text-gray-400 font-mono">{{ row.code }}</div>
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600">{{ row.patient_phone || '—' }}</td>
                                        <td class="px-4 py-2.5 text-gray-700">{{ row.service }}</td>
                                        <td class="px-4 py-2.5 text-gray-500 text-xs">{{ row.chair }}</td>
                                        <td class="px-4 py-2.5">
                                            <span :class="['inline-flex px-2.5 py-1 rounded-full text-xs font-bold', statusBadge(row.status)]">
                                                {{ row.status_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-gray-500 max-w-xs">
                                            <span v-if="row.notes" class="block truncate max-w-[200px]" :title="row.notes">{{ row.notes }}</span>
                                            <span v-else class="text-gray-300">—</span>
                                        </td>
                                        <td class="px-3 py-2.5 text-center">
                                            <a :href="'/schedule/appointments/' + row.id"
                                                class="inline-flex text-gray-300 hover:text-indigo-500 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- ── Print ────────────────────────────────────────────────────── -->
        <div class="hidden print:block print-area">
            <div class="print-header">
                <h1>BÁO CÁO LỊCH HẸN</h1>
                <p>{{ rangeLabel }}</p>
                <p class="sub">Xuất lúc {{ printTime }} · {{ totalFiltered }} lịch hẹn</p>
            </div>

            <template v-if="!useRange">
                <div v-for="group in groupedResult" :key="group.name" class="print-group">
                    <div class="print-doctor">Bác sĩ: {{ group.name }} <span class="print-cnt">({{ group.rows.length }})</span></div>
                    <table class="print-table">
                        <thead><tr>
                            <th>STT</th><th>Giờ</th><th>Bệnh nhân</th><th>SĐT</th>
                            <th>Dịch vụ</th><th>Ghế</th><th>TL(ph)</th><th>Trạng thái</th><th>Ghi chú</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="(row, i) in group.rows" :key="row.id">
                                <td class="c">{{ i+1 }}</td>
                                <td class="c mono">{{ row.scheduled_at }}→{{ row.ends_at }}</td>
                                <td><strong>{{ row.patient }}</strong><br><small class="gray">{{ row.code }}</small></td>
                                <td class="mono">{{ row.patient_phone || '—' }}</td>
                                <td>{{ row.service }}</td>
                                <td class="c">{{ row.chair }}</td>
                                <td class="c">{{ row.duration_minutes }}</td>
                                <td class="c"><strong>{{ row.status_label }}</strong></td>
                                <td class="small">{{ row.notes || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
            <template v-else>
                <div v-for="dayGroup in groupedResult" :key="dayGroup.date" class="print-date-section">
                    <div class="print-date">{{ fmtDateFull(dayGroup.date) }} — {{ dayGroup.total }} lịch hẹn</div>
                    <div v-for="group in dayGroup.byDoctor" :key="group.name" class="print-group">
                        <div class="print-doctor sub">{{ group.name }} <span class="print-cnt">({{ group.rows.length }})</span></div>
                        <table class="print-table">
                            <thead><tr>
                                <th>STT</th><th>Giờ</th><th>Bệnh nhân</th><th>SĐT</th>
                                <th>Dịch vụ</th><th>Ghế</th><th>TL(ph)</th><th>Trạng thái</th><th>Ghi chú</th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="(row, i) in group.rows" :key="row.id">
                                    <td class="c">{{ i+1 }}</td>
                                    <td class="c mono">{{ row.scheduled_at }}→{{ row.ends_at }}</td>
                                    <td><strong>{{ row.patient }}</strong><br><small class="gray">{{ row.code }}</small></td>
                                    <td class="mono">{{ row.patient_phone || '—' }}</td>
                                    <td>{{ row.service }}</td>
                                    <td class="c">{{ row.chair }}</td>
                                    <td class="c">{{ row.duration_minutes }}</td>
                                    <td class="c"><strong>{{ row.status_label }}</strong></td>
                                    <td class="small">{{ row.notes || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <div class="print-footer">
                <span>Tổng: {{ totalFiltered }} lịch hẹn</span>
                <span>DentalERP</span>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import AppLayout from '@/Components/Layout/AppLayout.vue';

// ── Props ─────────────────────────────────────────────────────────
const props = defineProps({
    appointments: { type: Array, default: () => [] },
    branches:     { type: Array, default: () => [] },
    doctors:      { type: Array, default: () => [] },
    statuses:     { type: Array, default: () => [] },
    filters:      { type: Object, default: () => ({}) },
});

// ── Status badge helper ───────────────────────────────────────────
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

// ── Filter state ──────────────────────────────────────────────────
const useRange = ref((props.filters.from ?? '') !== (props.filters.to ?? ''));
const f = ref({
    from:      props.filters.from ?? dayjs().startOf('month').format('YYYY-MM-DD'),
    to:        props.filters.to   ?? dayjs().endOf('month').format('YYYY-MM-DD'),
    search:    '',
    branch_id: '',
    doctor_id: '',
    status:    '',
});

const quickRanges = [
    { label: 'Hôm nay',
      from: dayjs().format('YYYY-MM-DD'),
      to:   dayjs().format('YYYY-MM-DD') },
    { label: 'Hôm qua',
      from: dayjs().subtract(1,'day').format('YYYY-MM-DD'),
      to:   dayjs().subtract(1,'day').format('YYYY-MM-DD') },
    { label: '7 ngày qua',
      from: dayjs().subtract(6,'day').format('YYYY-MM-DD'),
      to:   dayjs().format('YYYY-MM-DD') },
    { label: 'Tuần này',
      from: (()=>{ const d=dayjs(); return (d.day()===0?d.subtract(6,'day'):d.startOf('week').add(1,'day')).format('YYYY-MM-DD'); })(),
      to:   (()=>{ const d=dayjs(); return (d.day()===0?d:d.startOf('week').add(7,'day')).format('YYYY-MM-DD'); })() },
    { label: 'Tháng này',
      from: dayjs().startOf('month').format('YYYY-MM-DD'),
      to:   dayjs().endOf('month').format('YYYY-MM-DD') },
    { label: 'Tháng trước',
      from: dayjs().subtract(1,'month').startOf('month').format('YYYY-MM-DD'),
      to:   dayjs().subtract(1,'month').endOf('month').format('YYYY-MM-DD') },
];

const hasQuickFilters = computed(() =>
    f.value.search || f.value.branch_id || f.value.doctor_id || f.value.status
);
function clearQuick() {
    f.value.search = ''; f.value.branch_id = ''; f.value.doctor_id = ''; f.value.status = '';
}

function reloadRange() {
    router.get(route('reports.daily-schedule'), {
        from: f.value.from,
        to:   useRange.value ? f.value.to : f.value.from,
    }, { preserveScroll: true });
}

// ── Client-side filter ────────────────────────────────────────────
const filtered = computed(() => {
    const dateFrom = f.value.from;
    const dateTo   = useRange.value ? f.value.to : f.value.from;
    const q = f.value.search.trim().toLowerCase();

    return props.appointments.filter(a => {
        if (a.date < dateFrom || a.date > dateTo) return false;
        if (f.value.branch_id && String(a.branch_id) !== f.value.branch_id) return false;
        if (f.value.doctor_id && String(a.doctor_id) !== f.value.doctor_id) return false;
        if (f.value.status    && a.status !== f.value.status)    return false;
        if (q && !([a.patient, a.patient_phone, a.code, a.doctor, a.service]
            .join(' ').toLowerCase().includes(q))) return false;
        return true;
    });
});

const totalFiltered = computed(() => filtered.value.length);

// ── Grouping ──────────────────────────────────────────────────────
const groupedResult = computed(() => {
    if (!useRange.value) {
        const map = {};
        for (const a of filtered.value) {
            (map[a.doctor] ??= []).push(a);
        }
        return Object.entries(map).map(([name, rows]) => ({ name, rows }));
    }
    const dateMap = {};
    for (const a of filtered.value) {
        if (!dateMap[a.date]) dateMap[a.date] = { date: a.date, date_label: a.date_label, byDoc: {} };
        (dateMap[a.date].byDoc[a.doctor] ??= []).push(a);
    }
    return Object.values(dateMap).map(d => ({
        date:     d.date,
        date_label: d.date_label,
        total:    Object.values(d.byDoc).reduce((s, r) => s + r.length, 0),
        byDoctor: Object.entries(d.byDoc).map(([name, rows]) => ({ name, rows })),
    }));
});

// ── Helpers ───────────────────────────────────────────────────────
const VI_DAYS = ['Chủ nhật','Thứ Hai','Thứ Ba','Thứ Tư','Thứ Năm','Thứ Sáu','Thứ Bảy'];
function fmtDate(d)     { return d ? dayjs(d).format('DD/MM/YYYY') : ''; }
function fmtDateFull(d) { const dj = dayjs(d); return `${VI_DAYS[dj.day()]}, ${dj.format('DD/MM/YYYY')}`; }

const rangeLabel = computed(() => {
    if (!useRange.value) return fmtDate(f.value.from);
    return f.value.from === f.value.to
        ? fmtDate(f.value.from)
        : `${fmtDate(f.value.from)} – ${fmtDate(f.value.to)}`;
});
const printTime = computed(() => dayjs().format('HH:mm DD/MM/YYYY'));

function printPage() { window.print(); }

// ── CSV export ────────────────────────────────────────────────────
function exportCsv() {
    const BOM = '﻿';
    const esc = v => `"${String(v ?? '').replace(/"/g, '""')}"`;
    const headers = ['Ngày','Bác sĩ','Mã lịch','Giờ BĐ','Giờ KT','TL(ph)','Bệnh nhân','SĐT','Dịch vụ','Ghế','Trạng thái','Ghi chú'];
    const rows = filtered.value.map(r =>
        [r.date_label, r.doctor, r.code, r.scheduled_at, r.ends_at, r.duration_minutes,
         r.patient, r.patient_phone, r.service, r.chair, r.status_label, r.notes].map(esc).join(',')
    );
    const from = f.value.from, to = useRange.value ? f.value.to : f.value.from;
    const fname = from === to ? `lich-hen-${from}.csv` : `lich-hen-${from}-den-${to}.csv`;
    const blob = new Blob([BOM + [headers.join(','), ...rows].join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: fname });
    a.click(); URL.revokeObjectURL(a.href);
}
</script>

<style>
@media print {
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { font-family: Arial, sans-serif; font-size: 11pt; color: #111; }
    .print-area { width: 100%; }
    .print-header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #333; }
    .print-header h1  { font-size: 17pt; font-weight: bold; margin: 0 0 4px; }
    .print-header p   { margin: 2px 0; font-size: 11pt; color: #444; }
    .print-header .sub { font-size: 9pt; color: #888; }
    .print-date-section { margin-bottom: 24px; }
    .print-date  { font-size: 13pt; font-weight: bold; color: #1e3a8a; padding: 5px 0; margin-bottom: 8px; border-bottom: 2px solid #bfdbfe; }
    .print-group { margin-bottom: 16px; page-break-inside: avoid; }
    .print-doctor { background: #1e3a8a; color: #fff; font-weight: bold; font-size: 11.5pt; padding: 6px 12px; border-radius: 4px 4px 0 0; }
    .print-doctor.sub { background: #4338ca; font-size: 10.5pt; }
    .print-cnt   { font-size: 9pt; font-weight: normal; opacity: .85; margin-left: 10px; }
    .print-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
    .print-table th { background: #eff6ff; border: 1px solid #bfdbfe; padding: 4px 7px; font-weight: bold; text-align: left; white-space: nowrap; }
    .print-table td { border: 1px solid #e5e7eb; padding: 4px 7px; vertical-align: top; }
    .print-table tr:nth-child(even) td { background: #f9fafb; }
    .print-table .c    { text-align: center; }
    .print-table .mono { font-family: monospace; }
    .print-table .small { font-size: 8.5pt; }
    .print-table .gray  { color: #6b7280; }
    .print-footer { display: flex; justify-content: space-between; margin-top: 16px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 9pt; color: #666; }
}
</style>
