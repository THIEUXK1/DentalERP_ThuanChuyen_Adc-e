<template>
    <AppLayout title="Dịch vụ nha khoa">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Danh mục dịch vụ</h2>
                <Link v-if="can('services.manage')" :href="route('catalog.services.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Thêm dịch vụ
                </Link>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên hoặc mã dịch vụ..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="category" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả loại</option>
                    <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Mã</th>
                            <th class="px-4 py-3 text-left font-medium">Tên dịch vụ</th>
                            <th class="px-4 py-3 text-left font-medium">Loại</th>
                            <th class="px-4 py-3 text-right font-medium">Giá bán</th>
                            <th class="px-4 py-3 text-left font-medium">Thời gian</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="services.data.length === 0">
                            <td colspan="7" class="text-center py-8 text-gray-400">Chưa có dịch vụ</td>
                        </tr>
                        <tr v-for="s in services.data" :key="s.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ s.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ s.name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ s.category ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-800">{{ formatVnd(s.selling_price) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ s.duration_minutes }} phút</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="s.is_active ? 'green' : 'gray'">
                                    {{ s.is_active ? 'Hoạt động' : 'Vô hiệu' }}
                                </StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link v-if="can('services.manage')" :href="route('catalog.services.edit', s.id)"
                                    class="text-primary-600 hover:text-primary-800 text-xs font-medium">Sửa</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="services.links" :meta="services.meta" @navigate="url => router.get(url)" />
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
const props = defineProps({ services: Object, categories: Array, filters: Object });
const search   = ref(props.filters.search ?? '');
const category = ref(props.filters.category ?? '');

function applyFilters() {
    router.get(route('catalog.services.index'), { search: search.value, category: category.value }, { preserveState: true });
}
</script>
