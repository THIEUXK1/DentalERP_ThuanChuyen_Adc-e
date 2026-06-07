<template>
    <AppLayout title="Báo cáo doanh thu">
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">Báo cáo doanh thu</h2>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input type="date" v-model="from" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <span class="self-center text-gray-500">→</span>
                <input type="date" v-model="to" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="branchId" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option :value="null">Tất cả chi nhánh</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <button @click="applyFilters" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">Xem báo cáo</button>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-3 gap-4">
                <StatCard label="Tổng doanh thu" :value="formatVnd(totalRevenue)" icon="receipt" color="green" />
                <StatCard label="Tổng hoàn tiền" :value="formatVnd(totalRefunds)" icon="chart" color="red" />
                <StatCard label="Doanh thu thuần" :value="formatVnd(netRevenue)" icon="chart" color="teal" />
            </div>

            <!-- Revenue trend chart -->
            <ChartCard v-if="byDay.length > 0" title="Doanh thu theo ngày" type="bar" :data="chartData" :height="250" />

            <!-- By method -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b"><h3 class="text-sm font-semibold text-gray-700">Theo phương thức thanh toán</h3></div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Phương thức</th>
                            <th class="px-4 py-3 text-right font-medium">Tổng thu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="m in byMethod" :key="m.method" class="hover:bg-gray-50">
                            <td class="px-4 py-3 capitalize">{{ m.method }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ formatVnd(m.total) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatCard from '@/Components/Shared/StatCard.vue';
import ChartCard from '@/Components/Shared/ChartCard.vue';
import { useCurrency } from '@/composables/useCurrency';

const { formatVnd } = useCurrency();
const props = defineProps({ byDay: Array, byMethod: Array, totalRevenue: Number, totalRefunds: Number, netRevenue: Number, branches: Array, filters: Object });
const from     = ref(props.filters.from);
const to       = ref(props.filters.to);
const branchId = ref(props.filters.branchId ?? null);

function applyFilters() {
    router.get(route('reports.revenue'), { from: from.value, to: to.value, branch_id: branchId.value });
}

const chartData = computed(() => ({
    labels: props.byDay.map(r => r.day),
    datasets: [
        { label: 'Doanh thu', data: props.byDay.map(r => r.revenue), backgroundColor: '#0d9488', borderRadius: 2 },
        { label: 'Hoàn tiền', data: props.byDay.map(r => r.refunds), backgroundColor: '#ef4444', borderRadius: 2 },
    ],
}));
</script>
