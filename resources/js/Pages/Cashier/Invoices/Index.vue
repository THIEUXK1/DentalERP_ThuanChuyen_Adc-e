<template>
    <AppLayout title="Hóa đơn khách hàng">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Hóa đơn khách hàng</h2>
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
                            <th class="px-4 py-3 text-right font-medium">Tổng tiền</th>
                            <th class="px-4 py-3 text-right font-medium">Đã TT</th>
                            <th class="px-4 py-3 text-right font-medium">Còn nợ</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="7" class="text-center py-8 text-gray-400">Không có hóa đơn</td>
                        </tr>
                        <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ inv.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ inv.patient }}</td>
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
const props = defineProps({ invoices: Object, statuses: Array, branches: Array, filters: Object });
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(route('cashier.invoices.index'), { search: search.value, status: status.value }, { preserveState: true });
}
</script>
