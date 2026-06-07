<template>
    <AppLayout title="Follow-up Tasks">
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">Tasks Follow-up</h2>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
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
                            <th class="px-4 py-3 text-left font-medium">Đối tượng</th>
                            <th class="px-4 py-3 text-left font-medium">Ghi chú</th>
                            <th class="px-4 py-3 text-left font-medium">Ngày hẹn</th>
                            <th class="px-4 py-3 text-left font-medium">Phụ trách</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="tasks.data.length === 0">
                            <td colspan="6" class="text-center py-8 text-gray-400">Không có task</td>
                        </tr>
                        <tr v-for="t in tasks.data" :key="t.id"
                            :class="['hover:bg-gray-50', t.overdue ? 'bg-red-50/50' : '']">
                            <td class="px-4 py-3">
                                <Link :href="t.subject_url" class="text-primary-600 hover:text-primary-800 text-xs font-medium">{{ t.subject }}</Link>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ t.note ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600" :class="t.overdue ? 'text-red-600 font-medium' : ''">{{ t.due_date }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ t.assignee }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="t.overdue ? 'red' : t.status_color">
                                    {{ t.overdue ? 'Quá hạn' : t.status_label }}
                                </StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="t.status === 'pending'" @click="completeTask(t.id)"
                                    class="text-green-600 hover:text-green-800 text-xs font-medium">Hoàn thành</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="tasks.links" :meta="tasks.meta" @navigate="url => router.get(url)" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';

const props = defineProps({ tasks: Object, statuses: Array, filters: Object });
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(route('crm.tasks.index'), { status: status.value }, { preserveState: true });
}

function completeTask(id) {
    router.post(route('crm.tasks.complete', id));
}
</script>
