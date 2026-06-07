<template>
    <AppLayout title="Báo cáo công nợ">
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">Báo cáo công nợ / Aging</h2>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <select v-model="branchId" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option :value="null">Tất cả chi nhánh</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <button @click="applyFilters" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">Xem báo cáo</button>
            </div>

            <!-- Aging buckets -->
            <div class="grid grid-cols-5 gap-3">
                <div v-for="(amount, bucket) in buckets" :key="bucket" class="bg-white rounded-xl border border-gray-200 p-3 text-center">
                    <p class="text-xs text-gray-500 mb-1">{{ bucket === 'current' ? 'Hiện tại' : bucket + ' ngày' }}</p>
                    <p class="font-bold text-gray-800 text-sm">{{ formatVnd(amount) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-3">
                <p class="text-sm">Tổng công nợ chưa thu: <strong class="text-red-600">{{ formatVnd(totalOutstanding) }}</strong></p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Khách hàng</th>
                            <th class="px-4 py-3 text-right font-medium">Tổng nợ</th>
                            <th class="px-4 py-3 text-left font-medium">Hạn TT</th>
                            <th class="px-4 py-3 text-right font-medium">Số ngày quá hạn</th>
                            <th class="px-4 py-3 text-left font-medium">Nhóm</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="rows.length === 0">
                            <td colspan="5" class="text-center py-8 text-gray-400">Không có công nợ</td>
                        </tr>
                        <tr v-for="(r, i) in rows" :key="i" :class="['hover:bg-gray-50', r.days_overdue > 0 ? 'bg-red-50/30' : '']">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ r.patient }}</td>
                            <td class="px-4 py-3 text-right font-bold text-red-600">{{ formatVnd(r.remaining) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ r.due_date ?? '—' }}</td>
                            <td class="px-4 py-3 text-right" :class="r.days_overdue > 0 ? 'text-red-600 font-medium' : 'text-gray-400'">
                                {{ r.days_overdue > 0 ? r.days_overdue + ' ngày' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ r.bucket }}</td>
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
import { useCurrency } from '@/composables/useCurrency';

const { formatVnd } = useCurrency();
const props = defineProps({ rows: Array, buckets: Object, totalOutstanding: Number, branches: Array, filters: Object });
const branchId = ref(props.filters.branch_id ?? null);

function applyFilters() {
    router.get(route('reports.debt'), { branch_id: branchId.value });
}
</script>
