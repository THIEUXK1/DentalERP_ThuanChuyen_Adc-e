<template>
    <AppLayout :title="`Thu tiền: ${invoice.code}`">
        <div class="max-w-4xl space-y-4">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('cashier.invoices.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Hóa đơn</Link>
                        <span class="font-mono text-xs text-gray-500">{{ invoice.code }}</span>
                        <StatusBadge :color="invoice.status_color">{{ invoice.status_label }}</StatusBadge>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mt-1">{{ invoice.patient }}</h2>
                    <p class="text-sm text-gray-500">{{ invoice.patient_phone }} · {{ invoice.branch }}</p>
                </div>
                <div class="flex gap-2">
                    <a :href="route('cashier.invoices.receipt', invoice.id)" target="_blank"
                        class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">📄 In phiếu thu</a>
                    <button v-if="invoice.status !== 'cancelled' && invoice.status !== 'paid' && invoice.amount_paid === 0"
                        @click="doCancel" class="px-3 py-1.5 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50">Hủy HĐ</button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Left: Payment form + history -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Payment form -->
                    <div v-if="invoice.status !== 'paid' && invoice.status !== 'cancelled'" class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Thu tiền</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <FormInput label="Phương thức" :error="payForm.errors.method" required>
                                <select v-model="payForm.method" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                    <option v-for="m in methods" :key="m.value" :value="m.value">{{ m.label }}</option>
                                </select>
                            </FormInput>
                            <FormInput label="Số tiền (₫)" :error="payForm.errors.amount" required>
                                <input v-model="payForm.amount" type="number" :min="canRefund ? undefined : 1"
                                    :placeholder="`Còn nợ: ${formatVnd(invoice.amount_due)}`"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                            </FormInput>
                            <FormInput label="Ngày" :error="payForm.errors.payment_date" required>
                                <input v-model="payForm.payment_date" type="date" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                            </FormInput>
                            <FormInput label="Mã giao dịch" :error="payForm.errors.reference">
                                <input v-model="payForm.reference" type="text" placeholder="Tùy chọn" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                            </FormInput>
                        </div>
                        <div class="flex justify-between items-center mt-3">
                            <p v-if="payForm.errors.amount" class="text-xs text-red-500">{{ payForm.errors.amount }}</p>
                            <div class="ml-auto flex gap-2">
                                <button @click="fillFullAmount" class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50">
                                    Thu đủ ({{ formatVnd(invoice.amount_due) }})
                                </button>
                                <button @click="submitPayment" :disabled="payForm.processing"
                                    class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                                    Xác nhận thanh toán
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Payment history -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b">
                            <h3 class="text-sm font-semibold text-gray-700">Lịch sử thanh toán</h3>
                        </div>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50/50 text-gray-600">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium">Ngày</th>
                                    <th class="px-4 py-2 text-left font-medium">Phương thức</th>
                                    <th class="px-4 py-2 text-right font-medium">Số tiền</th>
                                    <th class="px-4 py-2 text-left font-medium">Người thu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="payments.length === 0">
                                    <td colspan="4" class="text-center py-4 text-gray-400">Chưa có thanh toán</td>
                                </tr>
                                <tr v-for="p in payments" :key="p.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-2.5">{{ p.payment_date }}</td>
                                    <td class="px-4 py-2.5">
                                        <StatusBadge :color="p.method_color">{{ p.method_label }}</StatusBadge>
                                    </td>
                                    <td class="px-4 py-2.5 text-right" :class="p.amount < 0 ? 'text-red-600' : 'text-green-700'">
                                        {{ p.amount < 0 ? '-' : '' }}{{ formatVnd(Math.abs(p.amount)) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ p.creator }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Debt summary + discount -->
                <div class="space-y-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700">Công nợ</h3>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Tổng hóa đơn</span><span>{{ formatVnd(invoice.total) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Giảm giá</span><span class="text-red-600">-{{ formatVnd(invoice.discount) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Đã thanh toán</span><span class="text-green-600">{{ formatVnd(invoice.amount_paid) }}</span></div>
                            <div class="flex justify-between font-bold border-t pt-2">
                                <span>Còn nợ</span>
                                <span :class="invoice.amount_due > 0 ? 'text-red-600' : 'text-green-600'">{{ formatVnd(invoice.amount_due) }}</span>
                            </div>
                        </div>
                        <StatusBadge v-if="debt" :color="debt.status_color" class="w-full justify-center">
                            {{ debt.status_label }}
                        </StatusBadge>
                    </div>

                    <!-- Discount (gated) -->
                    <div v-if="canDiscount && invoice.status !== 'paid' && invoice.status !== 'cancelled'" class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Giảm giá hóa đơn</h3>
                        <div class="flex gap-2">
                            <input v-model="discountAmount" type="number" min="0" placeholder="Số tiền giảm (₫)"
                                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                            <button @click="applyDiscount"
                                class="px-3 py-2 text-sm bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">Áp dụng</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import FormInput from '@/Components/Shared/FormInput.vue';
import { useCurrency } from '@/composables/useCurrency';
import dayjs from 'dayjs';

const { formatVnd } = useCurrency();
const props = defineProps({ invoice: Object, payments: Array, debt: Object, methods: Array, canRefund: Boolean, canDiscount: Boolean });

const discountAmount = ref(props.invoice.discount);
const payForm = useForm({
    amount:       '',
    method:       'cash',
    payment_date: dayjs().format('YYYY-MM-DD'),
    reference:    '',
    notes:        '',
});

function fillFullAmount() {
    payForm.amount = props.invoice.amount_due;
}

function submitPayment() {
    payForm.post(route('cashier.invoices.payments.store', props.invoice.id), {
        onSuccess: () => payForm.reset('amount', 'reference', 'notes'),
    });
}

function applyDiscount() {
    router.post(route('cashier.invoices.discount', props.invoice.id), { discount: discountAmount.value });
}

function doCancel() {
    if (confirm('Bạn muốn hủy hóa đơn này?')) {
        router.post(route('cashier.invoices.cancel', props.invoice.id));
    }
}
</script>
