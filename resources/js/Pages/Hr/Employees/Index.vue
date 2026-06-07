<template>
    <AppLayout title="Nhân viên">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Nhân viên</h2>
                <Link v-if="can('employees.create')" :href="route('employees.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Thêm nhân viên
                </Link>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên hoặc mã nhân viên..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="roleType" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả chức danh</option>
                    <option v-for="r in roleTypes" :key="r.value" :value="r.value">{{ r.label }}</option>
                </select>
                <select v-model="branchId" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả chi nhánh</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Mã</th>
                            <th class="px-4 py-3 text-left font-medium">Họ tên</th>
                            <th class="px-4 py-3 text-left font-medium">Chức danh</th>
                            <th class="px-4 py-3 text-left font-medium">Chi nhánh</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="employees.data.length === 0">
                            <td colspan="6" class="text-center py-8 text-gray-400">Chưa có nhân viên</td>
                        </tr>
                        <tr v-for="e in employees.data" :key="e.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ e.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ e.full_name }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="e.role_color">{{ e.role_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ e.branch }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="e.is_active ? 'green' : 'gray'">
                                    {{ e.is_active ? 'Hoạt động' : 'Vô hiệu' }}
                                </StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link v-if="can('employees.manage')" :href="route('employees.edit', e.id)"
                                    class="text-primary-600 hover:text-primary-800 text-xs font-medium">Sửa</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="employees.links" :meta="employees.meta" @navigate="url => router.get(url)" />
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
const props = defineProps({ employees: Object, branches: Array, roleTypes: Array, filters: Object });
const search   = ref(props.filters.search ?? '');
const roleType = ref(props.filters.role_type ?? '');
const branchId = ref(props.filters.branch_id ?? '');

function applyFilters() {
    router.get(route('employees.index'), { search: search.value, role_type: roleType.value, branch_id: branchId.value }, { preserveState: true });
}
</script>
