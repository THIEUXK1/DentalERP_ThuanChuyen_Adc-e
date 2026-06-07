<template>
    <AppLayout title="Hoa hồng">
        <div class="space-y-5">
            <!-- Header + nav tabs -->
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Hoa hồng nhân viên</h2>
                <Link v-if="can('commissions.manage')" :href="route('hr.commissions.rules')"
                    class="text-sm text-primary-600 hover:text-primary-800">Quản lý quy tắc →</Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 items-center">
                <input type="month" v-model="period" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="selectedEmployee" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả nhân viên</option>
                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                </select>
                <select v-model="selectedStatus" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-3 gap-4">
                <div v-for="s in statuses" :key="s.value"
                    class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">{{ s.label }}</p>
                    <p class="text-xl font-bold text-gray-800">{{ formatVnd(summaryByStatus[s.value]?.total ?? 0) }}</p>
                    <p class="text-xs text-gray-400">{{ summaryByStatus[s.value]?.count ?? 0 }} khoản</p>
                </div>
            </div>

            <!-- Transactions table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div v-if="transactions.length === 0" class="text-center text-gray-400 text-sm py-10">
                    Không có hoa hồng trong kỳ này
                </div>
                <table v-else class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-medium">Nhân viên</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-medium">Khách hàng</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-medium">Hóa đơn</th>
                            <th class="text-right px-4 py-3 text-xs text-gray-500 font-medium">Doanh thu HĐ</th>
                            <th class="text-right px-4 py-3 text-xs text-gray-500 font-medium">Hoa hồng</th>
                            <th class="text-center px-4 py-3 text-xs text-gray-500 font-medium">Trạng thái</th>
                            <th v-if="can('commissions.manage')" class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="t in transactions" :key="t.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ t.employee_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ t.patient_name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ t.invoice_code }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ formatVnd(t.invoice_total) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-teal-700">{{ formatVnd(t.amount) }}</td>
                            <td class="px-4 py-3 text-center">
                                <StatusBadge :color="t.status_color">{{ t.status_label }}</StatusBadge>
                            </td>
                            <td v-if="can('commissions.manage')" class="px-4 py-3 text-right">
                                <button v-if="t.status === 'pending'"
                                    @click="approve(t)"
                                    class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Duyệt</button>
                                <button v-else-if="t.status === 'approved'"
                                    @click="markPaid(t)"
                                    class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">Đã trả</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';
import { useCurrency } from '@/composables/useCurrency';

const { hasPermission: can } = usePermission();
const { formatVnd } = useCurrency();

const props = defineProps({
    transactions: Array,
    summary:      Object,
    employees:    Array,
    statuses:     Array,
    filters:      Object,
});

const period           = ref(props.filters.period ?? '');
const selectedEmployee = ref(props.filters.employeeId ?? '');
const selectedStatus   = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(route('hr.commissions.index'), {
        period:      period.value,
        employee_id: selectedEmployee.value || undefined,
        status:      selectedStatus.value   || undefined,
    }, { preserveState: true });
}

// summary is keyed by status string from controller
const summaryByStatus = computed(() => props.summary ?? {});

function approve(t) {
    router.post(route('hr.commissions.approve', t.id));
}

function markPaid(t) {
    router.post(route('hr.commissions.mark-paid', t.id));
}
</script>
