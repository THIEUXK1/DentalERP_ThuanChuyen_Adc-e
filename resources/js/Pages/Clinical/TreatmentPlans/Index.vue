<template>
    <AppLayout title="Kế hoạch điều trị">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Kế hoạch điều trị</h2>
                <Link v-if="can('treatment_plans.create')" :href="route('clinical.treatment-plans.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Tạo kế hoạch
                </Link>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên khách hàng..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
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
                            <th class="px-4 py-3 text-left font-medium">Mã</th>
                            <th class="px-4 py-3 text-left font-medium">Khách hàng</th>
                            <th class="px-4 py-3 text-left font-medium">Bác sĩ</th>
                            <th class="px-4 py-3 text-right font-medium">Tổng tiền</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-left font-medium">Ngày tạo</th>
                            <th class="px-4 py-3 text-right font-medium">Xem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="plans.data.length === 0">
                            <td colspan="7" class="text-center py-8 text-gray-400">Chưa có kế hoạch điều trị</td>
                        </tr>
                        <tr v-for="p in plans.data" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ p.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <Link :href="route('clinical.treatment-plans.show', p.id)" class="hover:text-primary-600">{{ p.patient }}</Link>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ p.doctor }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">{{ formatVnd(p.net_total) }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="p.status_color">{{ p.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ p.created_at }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('clinical.treatment-plans.show', p.id)"
                                    class="text-primary-600 text-xs font-medium">Xem</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="plans.links" :meta="plans.meta" @navigate="url => router.get(url)" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';
import { useCurrency } from '@/composables/useCurrency';

const { hasPermission: can } = usePermission();
const { formatVnd } = useCurrency();
const props = defineProps({ plans: Object, statuses: Array, branches: Array, filters: Object });
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(route('clinical.treatment-plans.index'), { search: search.value, status: status.value }, { preserveState: true });
}
</script>
