<template>
    <AppLayout title="Báo cáo CRM">
        <div class="space-y-6">
            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <input type="date" v-model="from" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <span class="text-gray-400 text-sm">→</span>
                <input type="date" v-model="to" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <button @click="applyFilters" class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">Lọc</button>
            </div>

            <!-- Funnel KPIs -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                <FunnelCard label="Tổng Lead" :value="conversion.total" color="gray" />
                <FunnelCard label="Đã tiếp cận" :value="conversion.contacted" color="blue" />
                <FunnelCard label="Đủ điều kiện" :value="conversion.qualified" color="teal" />
                <FunnelCard label="Đã chuyển đổi" :value="conversion.converted" color="green" />
                <FunnelCard label="Đã mất" :value="conversion.lost" color="red" />
            </div>

            <!-- Conversion rate + funnel chart -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Tỷ lệ chuyển đổi tổng</h3>
                    <div class="flex items-end gap-2 mb-3">
                        <span class="text-4xl font-bold text-teal-600">{{ conversionRate }}%</span>
                        <span class="text-sm text-gray-400 mb-1">lead → khách hàng</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-teal-500 h-3 rounded-full transition-all" :style="{ width: conversionRate + '%' }"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">
                        {{ conversion.converted }} / {{ conversion.total }} lead được chuyển thành khách hàng
                    </p>
                </div>

                <!-- Lead funnel bar chart -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <ChartCard title="Phân bổ trạng thái Lead" type="bar" :data="funnelChartData" :height="180" />
                </div>
            </div>

            <!-- Leads by source table -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Chuyển đổi theo nguồn khách</h3>
                <div v-if="bySource.length === 0" class="text-center text-gray-400 text-sm py-6">
                    Không có dữ liệu trong khoảng thời gian này
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 text-xs text-gray-500 font-medium">Nguồn</th>
                            <th class="text-right py-2 text-xs text-gray-500 font-medium">Tổng Lead</th>
                            <th class="text-right py-2 text-xs text-gray-500 font-medium">Chuyển đổi</th>
                            <th class="text-right py-2 text-xs text-gray-500 font-medium">Tỷ lệ</th>
                            <th class="py-2 pl-4 text-xs text-gray-500 font-medium">Biểu đồ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in sortedBySource" :key="row.source" class="hover:bg-gray-50">
                            <td class="py-2">
                                <span :class="`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-${sourceColor(row.source)}-100 text-${sourceColor(row.source)}-700`">
                                    {{ sourceLabel(row.source) }}
                                </span>
                            </td>
                            <td class="py-2 text-right text-gray-700">{{ row.total }}</td>
                            <td class="py-2 text-right text-teal-700 font-medium">{{ row.converted }}</td>
                            <td class="py-2 text-right font-semibold" :class="row.rate >= 50 ? 'text-green-600' : row.rate >= 25 ? 'text-yellow-600' : 'text-red-500'">
                                {{ row.rate }}%
                            </td>
                            <td class="py-2 pl-4">
                                <div class="w-24 bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-teal-500 h-1.5 rounded-full" :style="{ width: row.rate + '%' }"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Source pie chart -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 max-w-md">
                <ChartCard v-if="bySource.length > 0"
                    title="Tổng lead theo nguồn"
                    type="doughnut"
                    :data="sourceChartData"
                    :height="220"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import ChartCard from '@/Components/Shared/ChartCard.vue';

const props = defineProps({
    conversion: Object,
    bySource:   { type: Array, default: () => [] },
    filters:    Object,
});

const from = ref(props.filters.from ?? '');
const to   = ref(props.filters.to   ?? '');

function applyFilters() {
    router.get(route('reports.crm'), { from: from.value, to: to.value }, { preserveState: true });
}

const conversionRate = computed(() =>
    props.conversion.total > 0
        ? Math.round(props.conversion.converted / props.conversion.total * 100)
        : 0
);

const sortedBySource = computed(() => [...props.bySource].sort((a, b) => b.total - a.total));

const SOURCE_LABELS = {
    facebook: 'Facebook', zalo: 'Zalo', google: 'Google',
    referral: 'Giới thiệu', walk_in: 'Khách vãng lai', other: 'Khác',
};
const SOURCE_COLORS = {
    facebook: 'blue', zalo: 'teal', google: 'red',
    referral: 'purple', walk_in: 'gray', other: 'orange',
};
const SOURCE_HEX = {
    facebook: '#3b82f6', zalo: '#14b8a6', google: '#ef4444',
    referral: '#a855f7', walk_in: '#9ca3af', other: '#f97316',
};

function sourceLabel(s) { return SOURCE_LABELS[s] ?? s; }
function sourceColor(s) { return SOURCE_COLORS[s] ?? 'gray'; }

const funnelChartData = computed(() => ({
    labels: ['Tổng', 'Tiếp cận', 'Đủ ĐK', 'Chuyển đổi', 'Mất'],
    datasets: [{
        label: 'Lead',
        data: [
            props.conversion.total,
            props.conversion.contacted,
            props.conversion.qualified,
            props.conversion.converted,
            props.conversion.lost,
        ],
        backgroundColor: ['#9ca3af', '#3b82f6', '#14b8a6', '#22c55e', '#ef4444'],
        borderRadius: 4,
    }],
}));

const sourceChartData = computed(() => ({
    labels: props.bySource.map(r => sourceLabel(r.source)),
    datasets: [{
        data: props.bySource.map(r => r.total),
        backgroundColor: props.bySource.map(r => SOURCE_HEX[r.source] ?? '#9ca3af'),
    }],
}));

const FunnelCard = defineComponent({
    props: { label: String, value: Number, color: String },
    setup(p) {
        return () => h('div', {
            class: `bg-white rounded-xl border border-gray-200 p-4 text-center`,
        }, [
            h('p', { class: 'text-xs text-gray-500 mb-1' }, p.label),
            h('p', { class: `text-2xl font-bold text-${p.color}-600` }, p.value ?? 0),
        ]);
    },
});
</script>
