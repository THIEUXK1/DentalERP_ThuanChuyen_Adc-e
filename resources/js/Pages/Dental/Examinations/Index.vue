<template>
    <AppLayout title="Phiếu khám & Chẩn đoán">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Phiếu khám & Chẩn đoán</h1>
                <a :href="route('dental.examinations.create')"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium">
                    + Tạo phiếu khám
                </a>
            </div>

            <!-- Filters -->
            <div class="flex gap-3 flex-wrap">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên, SĐT bệnh nhân..."
                    class="border rounded-lg px-3 py-2 text-sm w-56" />
                <select v-model="status" @change="applyFilters" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <select v-model="doctor_id" @change="applyFilters" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="">Tất cả bác sĩ</option>
                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.full_name }}</option>
                </select>
                <button @click="applyFilters" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">Lọc</button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã phiếu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bệnh nhân</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bác sĩ khám</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lý do khám</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày khám</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="e in examinations.data" :key="e.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-mono text-gray-500">{{ e.code }}</td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-800">{{ e.patient_name }}</div>
                                <div class="text-xs text-gray-400">{{ e.patient_phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ e.doctor_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{{ e.chief_complaint || '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ e.examined_at }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="`inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-${e.status_color}-100 text-${e.status_color}-700`">
                                    {{ e.status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a :href="route('dental.examinations.show', e.id)" class="text-xs text-blue-600 hover:underline">Xem</a>
                            </td>
                        </tr>
                        <tr v-if="!examinations.data?.length">
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">Chưa có phiếu khám nào</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :links="examinations.links" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Pagination from '@/Components/Shared/Pagination.vue';

const props = defineProps({ examinations: Object, doctors: Array, statuses: Array, filters: Object });

const search    = ref(props.filters?.search ?? '');
const status    = ref(props.filters?.status ?? '');
const doctor_id = ref(props.filters?.doctor_id ?? '');

function applyFilters() {
    router.get(route('dental.examinations.index'), { search: search.value, status: status.value, doctor_id: doctor_id.value }, { preserveState: true });
}
</script>
