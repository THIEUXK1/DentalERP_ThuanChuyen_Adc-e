<template>
    <AppLayout title="Lead / CRM">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Pipeline Lead</h2>
                <Link v-if="can('leads.create')" :href="route('crm.leads.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Thêm lead
                </Link>
            </div>

            <!-- Funnel summary -->
            <div class="flex gap-2 overflow-x-auto pb-1">
                <button v-for="s in statuses" :key="s.value"
                    @click="filterStatus(s.value)"
                    :class="[
                        'flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium border transition-colors',
                        status === s.value ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'
                    ]">
                    {{ s.label }}
                </button>
                <button @click="filterStatus('')"
                    :class="['flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium border', !status ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-300']">
                    Tất cả
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên hoặc SĐT..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="source" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả nguồn</option>
                    <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Mã</th>
                            <th class="px-4 py-3 text-left font-medium">Họ tên</th>
                            <th class="px-4 py-3 text-left font-medium">SĐT</th>
                            <th class="px-4 py-3 text-left font-medium">Nguồn</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-left font-medium">Phụ trách</th>
                            <th class="px-4 py-3 text-right font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="leads.data.length === 0">
                            <td colspan="7" class="text-center py-8 text-gray-400">Không có lead nào</td>
                        </tr>
                        <tr v-for="l in leads.data" :key="l.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ l.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <Link :href="route('crm.leads.show', l.id)" class="hover:text-primary-600">{{ l.name }}</Link>
                                <span v-if="l.converted" class="ml-1 text-xs text-green-600">✓ Đã chuyển</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ l.phone }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge v-if="l.source" :color="l.source_color">{{ l.source_label }}</StatusBadge>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="l.status_color">{{ l.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ l.assigned_to ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('crm.leads.show', l.id)" class="text-primary-600 text-xs font-medium">Xem</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="leads.links" :meta="leads.meta" @navigate="url => router.get(url)" />
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
const props = defineProps({ leads: Object, statuses: Array, sources: Array, assignees: Array, filters: Object });

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const source = ref(props.filters.source ?? '');

function applyFilters() {
    router.get(route('crm.leads.index'), { search: search.value, status: status.value, source: source.value }, { preserveState: true });
}

function filterStatus(val) {
    status.value = val;
    applyFilters();
}
</script>
