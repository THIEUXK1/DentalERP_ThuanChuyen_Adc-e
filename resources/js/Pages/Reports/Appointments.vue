<template>
    <AppLayout title="Báo cáo lịch hẹn">
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">Báo cáo lịch hẹn</h2>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input type="date" v-model="from" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <span class="self-center text-gray-500">→</span>
                <input type="date" v-model="to" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <button @click="applyFilters" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">Xem báo cáo</button>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-sm text-gray-500">Tổng: <strong class="text-gray-800">{{ total }}</strong> lịch hẹn</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Bác sĩ</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-medium">Số lượng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="rows.length === 0">
                            <td colspan="3" class="text-center py-8 text-gray-400">Chưa có dữ liệu</td>
                        </tr>
                        <tr v-for="(r, i) in rows" :key="i" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ r.doctor_name }}</td>
                            <td class="px-4 py-3 capitalize text-gray-600">{{ r.status }}</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-800">{{ r.count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ rows: Array, total: Number, branches: Array, filters: Object });
const from = ref(props.filters.from);
const to   = ref(props.filters.to);

function applyFilters() {
    router.get(route('reports.appointments'), { from: from.value, to: to.value });
}
</script>
