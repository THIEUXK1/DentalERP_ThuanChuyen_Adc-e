<template>
    <AppLayout title="Hiệu suất & KPI nhân viên">
        <div class="space-y-5">

            <!-- ── Page header ───────────────────────────────────────────── -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Hiệu suất &amp; KPI nhân viên</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Doanh số thực thu, phân bổ KPI và hoa hồng của toàn bộ nhân sự trong kỳ
                    </p>
                </div>
                <div class="flex items-center gap-1.5">
                    <button @click="shiftPeriod(-1)" class="w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <span class="px-3 py-1.5 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-lg tabular-nums">
                        Kỳ {{ periodLabel }}
                    </span>
                    <button @click="shiftPeriod(1)" class="w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            <!-- ── Filters ───────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Kỳ</label>
                    <input v-model="period" type="month" class="filter-input" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Chi nhánh</label>
                    <select v-model="branchId" class="filter-input">
                        <option value="">Tất cả chi nhánh</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <button @click="applyFilters"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 font-medium">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Xem báo cáo
                </button>

                <div class="flex-1"></div>

                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tìm nhân viên</label>
                    <input v-model="search" type="search" placeholder="Tên hoặc mã…" class="filter-input w-52" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Vai trò</label>
                    <select v-model="roleFilter" class="filter-input">
                        <option value="">Tất cả vai trò</option>
                        <option v-for="r in roleOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600 pb-2 cursor-pointer select-none">
                    <input v-model="onlyActive" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    Chỉ người có số liệu
                </label>
            </div>

            <!-- ── Hero cards ────────────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl p-5 text-white shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-indigo-100 text-sm font-medium">Tổng KPI kỳ này</span>
                        <span class="text-2xl">🏆</span>
                    </div>
                    <p class="text-2xl font-bold tabular-nums">{{ formatVnd(totals.kpi_total) }}</p>
                    <p class="text-indigo-200 text-xs mt-1">{{ totals.alloc_count }} lượt phân bổ · {{ totals.contributors }} người</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-5 text-white shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-emerald-100 text-sm font-medium">Doanh thu thực thu</span>
                        <span class="text-2xl">💰</span>
                    </div>
                    <p class="text-2xl font-bold tabular-nums">{{ formatVnd(totals.revenue) }}</p>
                    <p class="text-emerald-200 text-xs mt-1">{{ totals.case_count }} ca điều trị có thu</p>
                </div>
                <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-5 text-white shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-amber-100 text-sm font-medium">Tổng chi trả nhân sự</span>
                        <span class="text-2xl">🧾</span>
                    </div>
                    <p class="text-2xl font-bold tabular-nums">{{ formatVnd(totals.total_earning) }}</p>
                    <p class="text-amber-100 text-xs mt-1">KPI + hoa hồng {{ formatVnd(totals.commission) }}</p>
                </div>
                <div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl p-5 text-white shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sky-100 text-sm font-medium">Tỷ lệ KPI / doanh thu</span>
                        <span class="text-2xl">📊</span>
                    </div>
                    <p class="text-2xl font-bold tabular-nums">{{ totals.kpi_ratio }}%</p>
                    <p class="text-sky-200 text-xs mt-1">Bình quân {{ formatVnd(totals.avg_per_person) }} / người</p>
                </div>
            </div>

            <!-- ── KPI status pipeline ───────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700">Dòng chảy trạng thái KPI</h3>
                    <span class="text-xs text-gray-400">Tổng {{ formatVnd(totals.kpi_total) }}</span>
                </div>
                <div class="flex h-3 rounded-full overflow-hidden bg-gray-100">
                    <div v-for="s in statusSegments" :key="s.key" :class="s.bar"
                        :style="{ width: s.percent + '%' }" :title="`${s.label}: ${formatVnd(s.value)}`"></div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mt-4">
                    <div v-for="s in statusSegments" :key="s.key" class="rounded-lg border border-gray-100 px-3 py-2">
                        <div class="flex items-center gap-1.5">
                            <span :class="['w-2 h-2 rounded-full', s.bar]"></span>
                            <span class="text-xs text-gray-500">{{ s.label }}</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-800 tabular-nums mt-0.5">{{ formatVnd(s.value) }}</p>
                        <p class="text-[11px] text-gray-400">{{ s.percent.toFixed(1) }}%</p>
                    </div>
                </div>
                <p v-if="totals.kpi_reversed > 0" class="text-xs text-rose-500 mt-3">
                    Đã đảo trong kỳ: {{ formatVnd(totals.kpi_reversed) }} (không tính vào tổng KPI)
                </p>
            </div>

            <!-- ── Charts ────────────────────────────────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2">
                    <ChartCard title="Xu hướng 6 kỳ gần nhất — doanh thu &amp; KPI"
                        type="bar" :data="trendData" :options="trendOptions" :height="260" />
                </div>
                <ChartCard title="KPI theo vai trò phân bổ" type="doughnut"
                    :data="roleChartData" :options="roleChartOptions" :height="260" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Top 3 podium -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Top KPI trong kỳ</h3>
                    <div v-if="topEmployees.length === 0" class="text-sm text-gray-400 py-8 text-center">Chưa có phân bổ KPI</div>
                    <div v-else class="space-y-3">
                        <div v-for="(r, i) in topEmployees" :key="r.employee_id" class="flex items-center gap-3">
                            <span :class="['w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0', medalClass(i)]">
                                {{ i + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ r.employee }}</p>
                                    <p class="text-sm font-semibold text-indigo-600 tabular-nums flex-shrink-0">{{ formatVnd(r.kpi_total) }}</p>
                                </div>
                                <div class="h-1.5 bg-gray-100 rounded-full mt-1.5 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 rounded-full"
                                        :style="{ width: barWidth(r.kpi_total) + '%' }"></div>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">
                                    {{ r.role_label }} · {{ r.alloc_count }} phân bổ · DT {{ formatVnd(r.revenue) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top services -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Dịch vụ sinh KPI nhiều nhất</h3>
                    <div v-if="topServices.length === 0" class="text-sm text-gray-400 py-8 text-center">Chưa có dữ liệu</div>
                    <div v-else class="space-y-2.5">
                        <div v-for="s in topServices" :key="s.name">
                            <div class="flex items-baseline justify-between gap-2 text-sm">
                                <span class="text-gray-700 truncate">{{ s.name }}</span>
                                <span class="text-gray-800 font-semibold tabular-nums flex-shrink-0">{{ formatVnd(s.kpi) }}</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full"
                                    :style="{ width: serviceBarWidth(s.kpi) + '%' }"></div>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ s.alloc_count }} lượt · DT tính KPI {{ formatVnd(s.revenue) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Detail table ──────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">
                        Chi tiết theo nhân viên
                        <span class="text-gray-400 font-normal">({{ visibleRows.length }}/{{ rows.length }})</span>
                    </h3>
                    <span class="text-xs text-gray-400">Bấm vào dòng để xem tách trạng thái KPI</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-10">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nhân viên</th>
                                <th v-for="col in sortableColumns" :key="col.key"
                                    @click="toggleSort(col.key)"
                                    class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700 whitespace-nowrap">
                                    {{ col.label }}
                                    <span class="text-indigo-500">{{ sortKey === col.key ? (sortDir === 'desc' ? '▾' : '▴') : '' }}</span>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Hệ số</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="visibleRows.length === 0">
                                <td :colspan="sortableColumns.length + 3" class="px-4 py-10 text-center text-gray-400 text-sm">
                                    Không có nhân viên nào khớp bộ lọc
                                </td>
                            </tr>
                            <template v-for="(r, i) in visibleRows" :key="r.employee_id">
                                <tr class="hover:bg-indigo-50/40 cursor-pointer" @click="toggleExpand(r.employee_id)">
                                    <td class="px-3 py-3 text-xs text-gray-400 tabular-nums">{{ i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center flex-shrink-0">
                                                {{ initials(r.employee) }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-800 truncate">{{ r.employee }}</p>
                                                <p class="text-xs text-gray-400">
                                                    <span class="font-mono">{{ r.code }}</span>
                                                    <span class="mx-1">·</span>{{ r.role_label }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-600">{{ r.case_count || '—' }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums" :class="r.revenue ? 'text-gray-800 font-medium' : 'text-gray-300'">
                                        {{ r.revenue ? formatVnd(r.revenue) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-500">
                                        {{ r.avg_per_case ? formatVnd(r.avg_per_case) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden hidden sm:block">
                                                <div class="h-full bg-indigo-500 rounded-full" :style="{ width: barWidth(r.kpi_total) + '%' }"></div>
                                            </div>
                                            <span class="tabular-nums font-semibold" :class="r.kpi_total ? 'text-indigo-600' : 'text-gray-300'">
                                                {{ r.kpi_total ? formatVnd(r.kpi_total) : '—' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums" :class="r.commission ? 'text-emerald-600' : 'text-gray-300'">
                                        {{ r.commission ? formatVnd(r.commission) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums font-semibold" :class="r.total_earning ? 'text-gray-900' : 'text-gray-300'">
                                        {{ r.total_earning ? formatVnd(r.total_earning) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span :class="['px-1.5 py-0.5 rounded text-[11px] font-medium tabular-nums', factorClass(r.quality_factor)]"
                                            title="Hệ số chất lượng">CL {{ r.quality_factor }}</span>
                                        <span :class="['ml-1 px-1.5 py-0.5 rounded text-[11px] font-medium tabular-nums', factorClass(r.collection_factor)]"
                                            title="Hệ số thu hồi">TH {{ r.collection_factor }}</span>
                                    </td>
                                </tr>
                                <tr v-if="expanded.has(r.employee_id)" class="bg-gray-50/70">
                                    <td :colspan="sortableColumns.length + 3" class="px-4 py-4">
                                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                                            <div v-for="d in employeeBreakdown(r)" :key="d.label" class="rounded-lg bg-white border border-gray-100 px-3 py-2">
                                                <p class="text-[11px] text-gray-400">{{ d.label }}</p>
                                                <p class="text-sm font-semibold tabular-nums" :class="d.class ?? 'text-gray-800'">{{ d.value }}</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr class="font-semibold text-gray-800">
                                <td colspan="2" class="px-4 py-3 text-xs uppercase text-gray-500">Tổng cộng ({{ visibleRows.length }} người)</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ visibleTotals.case_count }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ formatVnd(visibleTotals.revenue) }}</td>
                                <td class="px-4 py-3 text-right text-gray-400">—</td>
                                <td class="px-4 py-3 text-right tabular-nums text-indigo-700">{{ formatVnd(visibleTotals.kpi_total) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-emerald-700">{{ formatVnd(visibleTotals.commission) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ formatVnd(visibleTotals.total_earning) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import ChartCard from '@/Components/Shared/ChartCard.vue';

const props = defineProps({
    rows:        { type: Array,  default: () => [] },
    totals:      { type: Object, default: () => ({}) },
    by_role:     { type: Array,  default: () => [] },
    top_services:{ type: Array,  default: () => [] },
    trend:       { type: Array,  default: () => [] },
    period:      String,
    branches:    { type: Array,  default: () => [] },
    filters:     { type: Object, default: () => ({}) },
});

const period   = ref(props.filters.period ?? props.period ?? '');
const branchId = ref(props.filters.branchId ?? '');

const search     = ref('');
const roleFilter = ref('');
const onlyActive = ref(true);
const sortKey    = ref('kpi_total');
const sortDir    = ref('desc');
const expanded   = ref(new Set());

const rows         = computed(() => props.rows ?? []);
const totals       = computed(() => props.totals ?? {});
const topServices  = computed(() => props.top_services ?? []);

const periodLabel = computed(() => {
    if (!props.period) return '—';
    const [y, m] = props.period.split('-');
    return `${m}/${y}`;
});

const roleOptions = computed(() => {
    const seen = new Map();
    rows.value.forEach(r => { if (r.role_type && !seen.has(r.role_type)) seen.set(r.role_type, r.role_label); });
    return [...seen].map(([value, label]) => ({ value, label }));
});

const sortableColumns = [
    { key: 'case_count',    label: 'Số ca' },
    { key: 'revenue',       label: 'Doanh thu' },
    { key: 'avg_per_case',  label: 'TB/ca' },
    { key: 'kpi_total',     label: 'KPI' },
    { key: 'commission',    label: 'Hoa hồng' },
    { key: 'total_earning', label: 'Tổng nhận' },
];

const visibleRows = computed(() => {
    const q = search.value.trim().toLowerCase();

    return rows.value
        .filter(r => !onlyActive.value || r.revenue > 0 || r.kpi_total > 0 || r.commission > 0)
        .filter(r => !roleFilter.value || r.role_type === roleFilter.value)
        .filter(r => !q || r.employee.toLowerCase().includes(q) || (r.code ?? '').toLowerCase().includes(q))
        .slice()
        .sort((a, b) => (sortDir.value === 'desc' ? 1 : -1) * ((b[sortKey.value] ?? 0) - (a[sortKey.value] ?? 0)));
});

const visibleTotals = computed(() => {
    const sum = key => visibleRows.value.reduce((s, r) => s + (r[key] ?? 0), 0);
    return {
        case_count:    sum('case_count'),
        revenue:       sum('revenue'),
        kpi_total:     sum('kpi_total'),
        commission:    sum('commission'),
        total_earning: sum('total_earning'),
    };
});

const topEmployees = computed(() =>
    rows.value.filter(r => r.kpi_total > 0).sort((a, b) => b.kpi_total - a.kpi_total).slice(0, 5)
);

const maxKpi = computed(() => Math.max(1, ...rows.value.map(r => r.kpi_total ?? 0)));
const maxServiceKpi = computed(() => Math.max(1, ...topServices.value.map(s => s.kpi ?? 0)));

const statusSegments = computed(() => {
    const total = totals.value.kpi_total || 0;
    const defs = [
        { key: 'kpi_paid',      label: 'Đã trả',   bar: 'bg-emerald-500' },
        { key: 'kpi_approved',  label: 'Đã duyệt', bar: 'bg-teal-500' },
        { key: 'kpi_pending',   label: 'Chờ duyệt', bar: 'bg-amber-400' },
        { key: 'kpi_accrued',   label: 'Tạm tính', bar: 'bg-slate-400' },
        { key: 'kpi_held',      label: 'Đang treo', bar: 'bg-orange-500' },
    ];
    return defs.map(d => {
        const value = totals.value[d.key] ?? 0;
        return { ...d, value, percent: total > 0 ? (value / total) * 100 : 0 };
    });
});

// ── Charts ──────────────────────────────────────────────────────────────
const trendData = computed(() => ({
    labels: (props.trend ?? []).map(t => t.label),
    datasets: [
        {
            type: 'bar',
            label: 'Doanh thu',
            data: (props.trend ?? []).map(t => t.revenue),
            backgroundColor: 'rgba(16, 185, 129, 0.75)',
            borderRadius: 6,
            yAxisID: 'y',
            order: 2,
        },
        {
            type: 'line',
            label: 'KPI',
            data: (props.trend ?? []).map(t => t.kpi),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.15)',
            borderWidth: 2,
            pointRadius: 3,
            tension: 0.35,
            fill: true,
            yAxisID: 'y1',
            order: 1,
        },
    ],
}));

const trendOptions = {
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16, font: { size: 11 } } },
        tooltip: { callbacks: { label: c => `${c.dataset.label}: ${formatVnd(c.parsed.y)}` } },
    },
    scales: {
        y:  { position: 'left',  ticks: { callback: v => formatShort(v), font: { size: 10 } }, grid: { color: '#f1f5f9' } },
        y1: { position: 'right', ticks: { callback: v => formatShort(v), font: { size: 10 } }, grid: { drawOnChartArea: false } },
        x:  { grid: { display: false }, ticks: { font: { size: 11 } } },
    },
};

const roleChartData = computed(() => ({
    labels: (props.by_role ?? []).map(r => r.label),
    datasets: [{
        data: (props.by_role ?? []).map(r => r.kpi),
        backgroundColor: ['#6366f1', '#14b8a6', '#f59e0b', '#a855f7', '#94a3b8'],
        borderWidth: 0,
    }],
}));

const roleChartOptions = {
    cutout: '62%',
    plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14, font: { size: 11 } } },
        tooltip: { callbacks: { label: c => `${c.label}: ${formatVnd(c.parsed)}` } },
    },
};

// ── Actions & helpers ───────────────────────────────────────────────────
function applyFilters() {
    router.get(route('reports.performance'), { period: period.value, branch_id: branchId.value }, { preserveState: true, preserveScroll: true });
}

function shiftPeriod(months) {
    const [y, m] = (props.period ?? '').split('-').map(Number);
    if (!y || !m) return;
    const d = new Date(y, m - 1 + months, 1);
    period.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    applyFilters();
}

function toggleSort(key) {
    if (sortKey.value === key) sortDir.value = sortDir.value === 'desc' ? 'asc' : 'desc';
    else { sortKey.value = key; sortDir.value = 'desc'; }
}

function toggleExpand(id) {
    const next = new Set(expanded.value);
    next.has(id) ? next.delete(id) : next.add(id);
    expanded.value = next;
}

function employeeBreakdown(r) {
    return [
        { label: 'Số phân bổ',      value: r.alloc_count },
        { label: 'Bệnh nhân',       value: r.patient_count },
        { label: 'DT tính KPI',     value: formatVnd(r.eligible_revenue) },
        { label: 'Chi phí trực tiếp', value: formatVnd(r.direct_cost) },
        { label: 'Quỹ KPI gộp',     value: formatVnd(r.kpi_pool) },
        { label: 'Đã trả',          value: formatVnd(r.kpi_paid),    class: 'text-emerald-600' },
        { label: 'Đã duyệt',        value: formatVnd(r.kpi_approved), class: 'text-teal-600' },
        { label: 'Chờ duyệt',       value: formatVnd(r.kpi_pending), class: 'text-amber-600' },
        { label: 'Tạm tính',        value: formatVnd(r.kpi_accrued), class: 'text-slate-500' },
        { label: 'Đang treo',       value: formatVnd(r.kpi_held),    class: 'text-orange-600' },
        { label: 'Đã đảo',          value: formatVnd(r.kpi_reversed), class: 'text-rose-600' },
    ];
}

function barWidth(value)        { return Math.round(((value ?? 0) / maxKpi.value) * 100); }
function serviceBarWidth(value) { return Math.round(((value ?? 0) / maxServiceKpi.value) * 100); }

function medalClass(i) {
    return ['bg-amber-100 text-amber-700', 'bg-slate-100 text-slate-600', 'bg-orange-100 text-orange-700'][i] ?? 'bg-gray-100 text-gray-500';
}

function factorClass(v) {
    if (v >= 1)    return 'bg-emerald-50 text-emerald-700';
    if (v >= 0.9)  return 'bg-amber-50 text-amber-700';
    return 'bg-rose-50 text-rose-700';
}

function initials(name) {
    return (name ?? '').trim().split(/\s+/).slice(-2).map(w => w[0] ?? '').join('').toUpperCase();
}

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }

function formatShort(v) {
    const n = Number(v) || 0;
    if (Math.abs(n) >= 1_000_000_000) return (n / 1_000_000_000).toFixed(1) + ' tỷ';
    if (Math.abs(n) >= 1_000_000)     return (n / 1_000_000).toFixed(0) + ' tr';
    if (Math.abs(n) >= 1_000)         return (n / 1_000).toFixed(0) + 'k';
    return String(n);
}
</script>

<style scoped>
.filter-input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none; }
</style>
