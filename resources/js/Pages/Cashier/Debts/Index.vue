<template>
    <AppLayout title="Công nợ khách hàng">
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">Công nợ chưa thu</h2>

            <!-- Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <StatCard label="Tổng công nợ chưa thu" :value="formatVnd(summary.total_outstanding)" icon="receipt" color="red" />
                <StatCard label="Số hóa đơn còn nợ" :value="summary.count" icon="users" color="yellow" />
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên hoặc SĐT khách hàng..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-64 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Khách hàng</th>
                            <th class="px-4 py-3 text-left font-medium">SĐT</th>
                            <th class="px-4 py-3 text-left font-medium">Mã HĐ</th>
                            <th class="px-4 py-3 text-right font-medium">Tổng tiền</th>
                            <th class="px-4 py-3 text-right font-medium">Đã trả</th>
                            <th class="px-4 py-3 text-right font-medium">Còn nợ</th>
                            <th class="px-4 py-3 text-left font-medium">Hạn TT</th>
                            <th class="px-4 py-3 text-left font-medium">TT</th>
                            <th class="px-4 py-3 text-right font-medium">Thu tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="debts.data.length === 0">
                            <td colspan="9" class="text-center py-8 text-gray-400">Không có công nợ</td>
                        </tr>
                        <tr v-for="d in debts.data" :key="d.id"
                            :class="['hover:bg-gray-50', d.overdue ? 'bg-red-50/30' : '']">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ d.patient }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ d.phone }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ d.invoice_code }}</td>
                            <td class="px-4 py-3 text-right">{{ formatVnd(d.amount) }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ formatVnd(d.paid) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-red-600">{{ formatVnd(d.remaining) }}</td>
                            <td class="px-4 py-3" :class="d.overdue ? 'text-red-600 font-medium' : 'text-gray-500'">
                                {{ d.due_date ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="d.status_color">{{ d.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('cashier.invoices.show', d.invoice_id)"
                                    class="text-primary-600 text-xs font-medium">Thu tiền</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="debts.links" :meta="debts.meta" @navigate="url => router.get(url)" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import StatCard from '@/Components/Shared/StatCard.vue';
import { useCurrency } from '@/composables/useCurrency';

const { formatVnd } = useCurrency();
const props = defineProps({ debts: Object, summary: Object, branches: Array, filters: Object });
const search = ref(props.filters.patient_search ?? '');

function applyFilters() {
    router.get(route('cashier.debts.index'), { patient_search: search.value }, { preserveState: true });
}
</script>
