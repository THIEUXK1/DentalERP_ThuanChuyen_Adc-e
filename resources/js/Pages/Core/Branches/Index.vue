<template>
    <AppLayout title="Chi nhánh">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Chi nhánh phòng khám</h2>
                <Link v-if="can('branches.manage')" :href="route('branches.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Thêm chi nhánh
                </Link>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên hoặc mã chi nhánh..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-64 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Mã</th>
                            <th class="px-4 py-3 text-left font-medium">Tên chi nhánh</th>
                            <th class="px-4 py-3 text-left font-medium">Điện thoại</th>
                            <th class="px-4 py-3 text-left font-medium">Quản lý</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="branches.data.length === 0">
                            <td colspan="6" class="text-center py-8 text-gray-400">Chưa có chi nhánh</td>
                        </tr>
                        <tr v-for="b in branches.data" :key="b.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ b.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ b.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ b.phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ b.manager ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="b.is_active ? 'green' : 'gray'">
                                    {{ b.is_active ? 'Hoạt động' : 'Vô hiệu' }}
                                </StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link v-if="can('branches.manage')" :href="route('branches.edit', b.id)"
                                    class="text-primary-600 hover:text-primary-800 text-xs font-medium mr-3">Sửa</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="branches.links" :meta="branches.meta" @navigate="url => router.get(url)" />
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
const props = defineProps({ branches: Object, filters: Object });
const search = ref(props.filters.search ?? '');

function applyFilters() {
    router.get(route('branches.index'), { search: search.value }, { preserveState: true });
}
</script>
