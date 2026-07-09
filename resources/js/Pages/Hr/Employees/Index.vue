<template>
    <AppLayout title="Cán bộ công nhân viên">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Cán bộ công nhân viên</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Quản lý thông tin nhân sự của phòng khám</p>
                </div>
                <Link v-if="can('employees.manage')" :href="route('employees.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Thêm cán bộ
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters"
                    placeholder="Tìm tên, mã NV, phòng ban, chức vụ..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-64 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="statusFilter" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <select v-model="departmentFilter" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả phòng ban</option>
                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
                <select v-model="branchFilter" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả chi nhánh</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="perPage" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option v-for="n in [20, 50, 100, 200]" :key="n" :value="n">{{ n }} / trang</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Mã NV</th>
                            <th class="px-4 py-3 text-left font-medium">Họ và tên</th>
                            <th class="px-4 py-3 text-left font-medium">Phòng ban</th>
                            <th class="px-4 py-3 text-left font-medium">Chức vụ</th>
                            <th class="px-4 py-3 text-left font-medium">Điện thoại</th>
                            <th class="px-4 py-3 text-left font-medium">Ngày vào làm</th>
                            <th class="px-4 py-3 text-left font-medium">Loại HĐ</th>
                            <th class="px-4 py-3 text-center font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-center font-medium">Hoạt động</th>
                            <th class="px-4 py-3 text-right font-medium">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="employees.data.length === 0">
                            <td colspan="10" class="text-center py-10 text-gray-400">Chưa có cán bộ nào</td>
                        </tr>
                        <tr v-for="e in employees.data" :key="e.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ e.code }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900">{{ e.full_name }}</div>
                                <div v-if="e.email" class="text-xs text-gray-400">{{ e.email }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ e.department || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ e.position || e.role_label }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ e.phone || '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ e.start_date || '—' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ e.contract_label || '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="`inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-${e.status_color}-100 text-${e.status_color}-700`">
                                    {{ e.status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button v-if="can('employees.manage')" type="button" @click="toggleActive(e)"
                                    :class="[
                                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                        e.is_active ? 'bg-green-500' : 'bg-gray-300',
                                    ]"
                                    :title="e.is_active ? 'Đang hoạt động - bấm để ngừng' : 'Ngừng hoạt động - bấm để kích hoạt'">
                                    <span :class="[
                                        'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                        e.is_active ? 'translate-x-6' : 'translate-x-1',
                                    ]" />
                                </button>
                                <span v-else
                                    :class="`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${e.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`">
                                    {{ e.is_active ? 'Bật' : 'Tắt' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-3">
                                <Link :href="route('employees.show', e.id)"
                                    class="text-xs text-primary-600 hover:text-primary-800 font-medium">Xem</Link>
                                <Link v-if="can('employees.manage')" :href="route('employees.edit', e.id)"
                                    class="text-xs text-gray-500 hover:text-gray-800 font-medium">Sửa</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="employees.links" :meta="employees.meta" @navigate="url => router.get(url, {}, { preserveState: true, preserveScroll: true })" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({
    employees:   Object,
    branches:    Array,
    departments: Array,
    statuses:    Array,
    filters:     Object,
});

const search          = ref(props.filters?.search ?? '');
const statusFilter    = ref(props.filters?.employment_status ?? '');
const departmentFilter = ref(props.filters?.department_id ?? '');
const branchFilter    = ref(props.filters?.branch_id ?? '');
const perPage         = ref(Number(props.filters?.per_page ?? 50));

function applyFilters() {
    router.get(route('employees.index'), {
        search: search.value,
        employment_status: statusFilter.value,
        department_id: departmentFilter.value,
        per_page: perPage.value,
        branch_id: branchFilter.value,
    }, { preserveState: true });
}

function toggleActive(employee) {
    router.patch(route('employees.toggle-active', employee.id), {}, { preserveScroll: true, preserveState: true });
}
</script>
