<template>
    <AppLayout title="Audit Log">
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">Nhật ký hoạt động</h2>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên người thực hiện..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="subjectType" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả loại</option>
                    <option v-for="t in subjectTypes" :key="t" :value="t">{{ t }}</option>
                </select>
                <input type="date" v-model="date" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Thời gian</th>
                            <th class="px-4 py-3 text-left font-medium">Người thực hiện</th>
                            <th class="px-4 py-3 text-left font-medium">Hành động</th>
                            <th class="px-4 py-3 text-left font-medium">Đối tượng</th>
                            <th class="px-4 py-3 text-left font-medium">Mô tả</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="logs.data.length === 0">
                            <td colspan="5" class="text-center py-8 text-gray-400">Không có nhật ký</td>
                        </tr>
                        <tr v-for="l in logs.data" :key="l.id" class="hover:bg-gray-50 text-xs">
                            <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ l.created_at }}</td>
                            <td class="px-4 py-2.5 font-medium text-gray-700">{{ l.causer }}</td>
                            <td class="px-4 py-2.5">
                                <StatusBadge :color="eventColor(l.event)">{{ l.event ?? 'log' }}</StatusBadge>
                            </td>
                            <td class="px-4 py-2.5 text-gray-600">{{ l.subject_type }} #{{ l.subject_id }}</td>
                            <td class="px-4 py-2.5 text-gray-500 max-w-xs truncate">{{ l.description }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="logs.links" :meta="logs.meta" @navigate="url => router.get(url)" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';

const props = defineProps({ logs: Object, subjectTypes: Array, filters: Object });
const search      = ref(props.filters.search ?? '');
const subjectType = ref(props.filters.subject_type ?? '');
const date        = ref(props.filters.date ?? '');

function applyFilters() {
    router.get(route('admin.activity-log.index'), { search: search.value, subject_type: subjectType.value, date: date.value }, { preserveState: true });
}

function eventColor(event) {
    return { created: 'green', updated: 'blue', deleted: 'red' }[event] ?? 'gray';
}
</script>
