<template>
    <AppLayout title="Báo cáo Lãi/Lỗ">
        <div class="space-y-6">
            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <input type="date" v-model="from" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <span class="text-gray-400 text-sm">→</span>
                <input type="date" v-model="to" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-if="branches.length > 0" v-model="selectedBranch"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option :value="null">Tất cả chi nhánh</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <button @click="applyFilters" class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">Lọc</button>
            </div>

            <!-- P&L Summary -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                    <p class="text-xs text-gray-500 mb-1">Doanh thu thu vào</p>
                    <p class="text-2xl font-bold text-green-600">{{ formatVnd(income) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                    <p class="text-xs text-gray-500 mb-1">Hoàn trả</p>
                    <p class="text-2xl font-bold text-orange-500">{{ formatVnd(refunds) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                    <p class="text-xs text-gray-500 mb-1">Tổng chi phí</p>
                    <p class="text-2xl font-bold text-red-600">{{ formatVnd(expenses) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 text-center border-2"
                    :class="net >= 0 ? 'border-green-400' : 'border-red-400'">
                    <p class="text-xs text-gray-500 mb-1">Lợi nhuận ròng</p>
                    <p class="text-2xl font-bold" :class="net >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ net >= 0 ? '+' : '' }}{{ formatVnd(net) }}
                    </p>
                </div>
            </div>

            <!-- Charts row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Revenue trend -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <ChartCard v-if="revenueByDay.length > 0"
                        title="Doanh thu thu vào theo ngày"
                        type="line"
                        :data="revenueChartData"
                        :height="200"
                    />
                    <div v-else class="text-center text-gray-400 text-sm py-10">Chưa có dữ liệu doanh thu</div>
                </div>

                <!-- Expenses by category -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <ChartCard v-if="expensesByCategory.length > 0"
                        title="Chi phí theo loại"
                        type="doughnut"
                        :data="expenseCategoryChartData"
                        :height="200"
                    />
                    <div v-else class="text-center text-gray-400 text-sm py-10">Chưa có phiếu chi</div>
                </div>
            </div>

            <!-- Expense breakdown table -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Chi tiết chi phí theo loại</h3>
                <div v-if="expensesByCategory.length === 0" class="text-center text-gray-400 text-sm py-4">
                    Không có chi phí trong khoảng này
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 text-xs text-gray-500 font-medium">Loại chi phí</th>
                            <th class="text-right py-2 text-xs text-gray-500 font-medium">Số tiền</th>
                            <th class="text-right py-2 text-xs text-gray-500 font-medium">Tỷ trọng</th>
                            <th class="py-2 pl-4 text-xs text-gray-500 font-medium">Biểu đồ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in expensesByCategory" :key="r.category">
                            <td class="py-2 text-gray-700">{{ categoryLabel(r.category) }}</td>
                            <td class="py-2 text-right font-semibold text-red-600">{{ formatVnd(r.total) }}</td>
                            <td class="py-2 text-right text-gray-500">
                                {{ expenses > 0 ? Math.round(r.total / expenses * 100) : 0 }}%
                            </td>
                            <td class="py-2 pl-4">
                                <div class="w-24 bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-red-400 h-1.5 rounded-full"
                                        :style="{ width: (expenses > 0 ? r.total / expenses * 100 : 0) + '%' }"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t border-gray-200 font-semibold">
                        <tr>
                            <td class="py-2 text-gray-700">Tổng chi phí</td>
                            <td class="py-2 text-right text-red-700">{{ formatVnd(expenses) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import ChartCard from '@/Components/Shared/ChartCard.vue';
import { useCurrency } from '@/composables/useCurrency';

const { formatVnd } = useCurrency();

const props = defineProps({
    income:              Number,
    refunds:             Number,
    expenses:            Number,
    net:                 Number,
    expensesByCategory:  { type: Array, default: () => [] },
    revenueByDay:        { type: Array, default: () => [] },
    branches:            Array,
    filters:             Object,
});

const from           = ref(props.filters.from ?? '');
const to             = ref(props.filters.to   ?? '');
const selectedBranch = ref(props.filters.branchId ?? null);

function applyFilters() {
    router.get(route('reports.profit-loss'), {
        from: from.value, to: to.value,
        branch_id: selectedBranch.value || undefined,
    }, { preserveState: true });
}

const CATEGORY_LABELS = {
    rent: 'Thuê mặt bằng', utilities: 'Điện/nước/internet', supplies: 'Vật tư tiêu hao',
    equipment: 'Thiết bị/sửa chữa', salary: 'Lương/thưởng', marketing: 'Marketing', other: 'Khác',
};
const CATEGORY_COLORS = ['#ef4444','#f97316','#eab308','#22c55e','#3b82f6','#a855f7','#9ca3af'];

function categoryLabel(c) { return CATEGORY_LABELS[c] ?? c; }

const revenueChartData = computed(() => ({
    labels: props.revenueByDay.map(r => r.day),
    datasets: [{
        label: 'Doanh thu',
        data: props.revenueByDay.map(r => r.revenue),
        borderColor: '#22c55e',
        backgroundColor: 'rgba(34,197,94,0.08)',
        fill: true, tension: 0.4, pointRadius: 2,
    }],
}));

const expenseCategoryChartData = computed(() => ({
    labels: props.expensesByCategory.map(r => categoryLabel(r.category)),
    datasets: [{
        data: props.expensesByCategory.map(r => r.total),
        backgroundColor: CATEGORY_COLORS.slice(0, props.expensesByCategory.length),
    }],
}));
</script>
