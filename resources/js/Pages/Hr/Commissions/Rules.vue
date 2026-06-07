<template>
    <AppLayout title="Quy tắc hoa hồng">
        <div class="space-y-5 max-w-3xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Quy tắc hoa hồng</h2>
                <Link :href="route('hr.commissions.index')" class="text-sm text-primary-600 hover:text-primary-800">← Danh sách hoa hồng</Link>
            </div>

            <!-- Add rule form -->
            <div class="bg-white rounded-xl border border-primary-200 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-700">Thêm quy tắc mới</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Nhân viên</label>
                        <select v-model="form.employee_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn --</option>
                            <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }} ({{ e.role }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Loại</label>
                        <select v-model="form.type"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">
                            {{ form.type === 'revenue_percentage' ? 'Tỷ lệ (%)' : 'Số tiền (VNĐ)' }}
                        </label>
                        <input v-model="form.value" type="number" min="0" step="0.5"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Ghi chú</label>
                    <input v-model="form.notes" type="text" placeholder="Ghi chú tùy chọn..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </div>
                <div class="flex justify-end">
                    <button @click="submitRule" :disabled="!form.employee_id || !form.value"
                        class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        Thêm quy tắc
                    </button>
                </div>
            </div>

            <!-- Rules list -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div v-if="rules.length === 0" class="text-center text-gray-400 text-sm py-8">
                    Chưa có quy tắc nào
                </div>
                <table v-else class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-medium">Nhân viên</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-medium">Loại</th>
                            <th class="text-right px-4 py-3 text-xs text-gray-500 font-medium">Giá trị</th>
                            <th class="px-4 py-3 text-xs text-gray-500 font-medium">Ghi chú</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in rules" :key="r.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ r.employee_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ r.type_label }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-teal-700">
                                {{ r.type === 'revenue_percentage' ? r.value + '%' : formatVnd(r.value) }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ r.notes ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button @click="deleteRule(r)"
                                    class="text-xs text-gray-300 hover:text-red-500">✕</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { useCurrency } from '@/composables/useCurrency';

const { formatVnd } = useCurrency();

const props = defineProps({
    rules:     Array,
    employees: Array,
    types:     Array,
});

const form = ref({ employee_id: '', type: 'revenue_percentage', value: '', notes: '' });

function submitRule() {
    router.post(route('hr.commissions.rules.store'), form.value, {
        onSuccess: () => { form.value = { employee_id: '', type: 'revenue_percentage', value: '', notes: '' }; },
    });
}

function deleteRule(r) {
    if (!confirm(`Xóa quy tắc hoa hồng của ${r.employee_name}?`)) return;
    router.delete(route('hr.commissions.rules.destroy', r.id));
}
</script>
