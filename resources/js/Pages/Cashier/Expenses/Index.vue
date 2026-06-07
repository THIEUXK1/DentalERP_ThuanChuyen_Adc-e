<template>
    <AppLayout title="Phiếu chi">
        <div class="space-y-5">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Phiếu chi nội bộ</h2>
                <button v-if="can('expenses.manage')" @click="showForm = !showForm"
                    class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    {{ showForm ? 'Đóng' : '+ Thêm phiếu chi' }}
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 items-center">
                <input type="date" v-model="from"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <span class="text-gray-400 text-sm">→</span>
                <input type="date" v-model="to"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="selectedCategory"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả loại</option>
                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
                <button @click="applyFilters" class="px-4 py-2 text-sm bg-gray-100 rounded-lg hover:bg-gray-200 text-gray-700">Lọc</button>
            </div>

            <!-- Add form -->
            <div v-if="showForm && can('expenses.manage')"
                class="bg-white rounded-xl border border-primary-200 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-700">Thêm phiếu chi</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Loại chi phí</label>
                        <select v-model="form.category"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn --</option>
                            <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs text-gray-500 mb-1 block">Mô tả</label>
                        <input v-model="form.description" type="text" placeholder="Nội dung chi..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Số tiền (VNĐ)</label>
                        <input v-model="form.amount" type="number" min="0" step="1000"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Ngày chi</label>
                        <input v-model="form.expense_date" type="date"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Hình thức</label>
                        <select v-model="form.payment_method"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn --</option>
                            <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Ghi chú</label>
                    <input v-model="form.notes" type="text"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="showForm = false" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600">Hủy</button>
                    <button @click="submit" :disabled="!form.category || !form.description || !form.amount || !form.expense_date"
                        class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50">Lưu</button>
                </div>
            </div>

            <!-- Summary by category -->
            <div v-if="byCategory.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white rounded-xl border border-gray-200 p-3 text-center col-span-2 sm:col-span-1">
                    <p class="text-xs text-gray-500 mb-1">Tổng chi</p>
                    <p class="text-xl font-bold text-red-600">{{ formatVnd(totalAmount) }}</p>
                </div>
                <div v-for="c in byCategory.slice(0,3)" :key="c.label"
                    class="bg-white rounded-xl border border-gray-200 p-3 text-center">
                    <p class="text-xs text-gray-500 mb-1">{{ c.label }}</p>
                    <p class="text-lg font-semibold text-gray-800">{{ formatVnd(c.total) }}</p>
                </div>
            </div>

            <!-- Expenses table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div v-if="expenses.length === 0" class="text-center text-gray-400 text-sm py-10">
                    Không có phiếu chi trong khoảng thời gian này
                </div>
                <table v-else class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-medium">Ngày</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-medium">Loại</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-medium">Mô tả</th>
                            <th class="text-right px-4 py-3 text-xs text-gray-500 font-medium">Số tiền</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-medium">Hình thức</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-medium">Người lập</th>
                            <th v-if="can('expenses.manage')" class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="e in expenses" :key="e.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-600">{{ e.expense_date }}</td>
                            <td class="px-4 py-3">
                                <span :class="`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-${e.category_color}-100 text-${e.category_color}-700`">
                                    {{ e.category_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ e.description }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-red-600">{{ formatVnd(e.amount) }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ e.payment_method ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ e.creator }}</td>
                            <td v-if="can('expenses.manage')" class="px-4 py-3 text-right">
                                <button @click="deleteExpense(e)"
                                    class="text-xs text-gray-300 hover:text-red-500">✕</button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700">Tổng cộng</td>
                            <td class="px-4 py-2 text-right font-bold text-red-700">{{ formatVnd(totalAmount) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';
import { useCurrency } from '@/composables/useCurrency';

const { hasPermission: can } = usePermission();
const { formatVnd } = useCurrency();

const props = defineProps({
    expenses:       Array,
    totalAmount:    Number,
    byCategory:     Array,
    categories:     Array,
    paymentMethods: Array,
    branches:       Array,
    filters:        Object,
});

const showForm        = ref(false);
const from            = ref(props.filters.from ?? '');
const to              = ref(props.filters.to   ?? '');
const selectedCategory = ref(props.filters.category ?? '');

const form = ref({
    category: '', description: '', amount: '', expense_date: new Date().toISOString().slice(0,10),
    payment_method: '', notes: '',
});

function applyFilters() {
    router.get(route('cashier.expenses.index'), {
        from: from.value, to: to.value,
        category: selectedCategory.value || undefined,
    }, { preserveState: true });
}

function submit() {
    router.post(route('cashier.expenses.store'), form.value, {
        onSuccess: () => {
            showForm.value = false;
            form.value = { category: '', description: '', amount: '', expense_date: new Date().toISOString().slice(0,10), payment_method: '', notes: '' };
        },
    });
}

function deleteExpense(e) {
    if (!confirm('Xóa phiếu chi này?')) return;
    router.delete(route('cashier.expenses.destroy', e.id));
}
</script>
