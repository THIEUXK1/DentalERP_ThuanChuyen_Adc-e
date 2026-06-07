<template>
    <AppLayout title="Khách hàng">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Danh sách khách hàng</h2>
                <Link v-if="can('patients.create')" :href="route('patients.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Thêm khách hàng
                </Link>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên, SĐT hoặc mã KH..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-64 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="branchId" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả chi nhánh</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="source" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả nguồn</option>
                    <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Mã</th>
                            <th class="px-4 py-3 text-left font-medium">Họ tên</th>
                            <th class="px-4 py-3 text-left font-medium">SĐT</th>
                            <th class="px-4 py-3 text-left font-medium">Nguồn</th>
                            <th class="px-4 py-3 text-left font-medium">Chi nhánh</th>
                            <th class="px-4 py-3 text-left font-medium">Ngày tạo</th>
                            <th class="px-4 py-3 text-right font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="patients.data.length === 0">
                            <td colspan="7" class="text-center py-8 text-gray-400">Chưa có khách hàng</td>
                        </tr>
                        <tr v-for="p in patients.data" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ p.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <Link :href="route('patients.show', p.id)" class="hover:text-primary-600">{{ p.full_name }}</Link>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ p.phone }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge v-if="p.source" color="blue">{{ p.source }}</StatusBadge>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ p.branch ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ p.created_at }}</td>
                            <td class="px-4 py-3 text-right flex justify-end gap-2">
                                <Link :href="route('patients.show', p.id)" class="text-primary-600 text-xs font-medium">Xem</Link>
                                <Link v-if="can('patients.edit')" :href="route('patients.edit', p.id)" class="text-gray-500 text-xs font-medium hover:text-gray-700">Sửa</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="patients.links" :meta="patients.meta" @navigate="url => router.get(url)" />
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

const { hasPermission: can } = usePermission();
const props = defineProps({ patients: Object, branches: Array, sources: Array, filters: Object });
const search   = ref(props.filters.search ?? '');
const branchId = ref(props.filters.branch_id ?? '');
const source   = ref(props.filters.source ?? '');

function applyFilters() {
    router.get(route('patients.index'), { search: search.value, branch_id: branchId.value, source: source.value }, { preserveState: true });
}
</script>
