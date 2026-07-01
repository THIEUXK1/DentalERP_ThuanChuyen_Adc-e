<template>
    <AppLayout title="Hóa đơn khách hàng">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Hóa đơn khách hàng</h2>
            </div>

            <!-- Filter banner -->
            <div v-if="selected_patient" class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 flex items-center justify-between text-sm text-indigo-800 shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Đang lọc hóa đơn của bệnh nhân: <strong class="text-indigo-950 font-semibold">{{ selected_patient.full_name }}</strong> (<span class="font-mono text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded">{{ selected_patient.code }}</span>)</span>
                </div>
                <Link :href="route('cashier.invoices.index')" class="text-xs font-semibold bg-white border border-indigo-200 text-indigo-600 hover:bg-indigo-50 px-3 py-1.5 rounded-lg shadow-sm transition-colors">
                    Xóa bộ lọc
                </Link>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên hoặc SĐT khách hàng..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-64 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="status" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Mã HĐ</th>
                            <th class="px-4 py-3 text-left font-medium">Khách hàng</th>
                            <th class="px-4 py-3 text-left font-medium">Kế hoạch / Đợt</th>
                            <th class="px-4 py-3 text-left font-medium">Đến hạn</th>
                            <th class="px-4 py-3 text-right font-medium">Tổng tiền</th>
                            <th class="px-4 py-3 text-right font-medium">Đã TT</th>
                            <th class="px-4 py-3 text-right font-medium">Còn nợ</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="9" class="text-center py-8 text-gray-400">Không có hóa đơn</td>
                        </tr>
                        <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ inv.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ inv.patient }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    <span v-if="inv.treatment_plan_code" class="font-mono text-xs text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded w-fit">
                                        {{ inv.treatment_plan_code }}
                                    </span>
                                    <span v-if="inv.installment_index !== null && inv.installment_index !== undefined"
                                        class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded w-fit">
                                        Đợt {{ inv.installment_index + 1 }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span v-if="inv.due_date" :class="isDuePast(inv.due_date_raw) && inv.amount_due > 0 ? 'text-red-600 font-semibold' : 'text-gray-700'">
                                    {{ inv.due_date }}
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ formatVnd(inv.total) }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ formatVnd(inv.amount_paid) }}</td>
                            <td class="px-4 py-3 text-right" :class="inv.amount_due > 0 ? 'text-red-600 font-medium' : 'text-gray-400'">
                                {{ formatVnd(inv.amount_due) }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="inv.status_color">{{ inv.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('cashier.invoices.show', inv.id)"
                                    class="text-primary-600 text-xs font-medium">Thu tiền</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="invoices.links" :meta="invoices.meta" @navigate="url => router.get(url)" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { useCurrency } from '@/composables/useCurrency';

const { formatVnd } = useCurrency();
const props = defineProps({ invoices: Object, statuses: Array, branches: Array, filters: Object, selected_patient: Object });
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const patientId = ref(props.filters.patient_id ?? '');

function isDuePast(dateRaw) {
    if (!dateRaw) return false;
    return new Date(dateRaw) < new Date(new Date().toDateString());
}

function applyFilters() {
    router.get(route('cashier.invoices.index'), {
        search: search.value,
        status: status.value,
        patient_id: patientId.value,
    }, { preserveState: true });
}
</script>
