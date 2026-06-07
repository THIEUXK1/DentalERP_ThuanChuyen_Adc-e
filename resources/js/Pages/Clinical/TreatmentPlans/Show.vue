<template>
    <AppLayout :title="`KHDT: ${plan.code}`">
        <div class="max-w-5xl space-y-4">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('clinical.treatment-plans.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Kế hoạch</Link>
                        <span class="font-mono text-xs text-gray-500">{{ plan.code }}</span>
                        <StatusBadge :color="plan.status_color">{{ plan.status_label }}</StatusBadge>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mt-1">{{ plan.patient }}</h2>
                    <p class="text-sm text-gray-500">BS: {{ plan.doctor }} · Tư vấn: {{ plan.consultant }}</p>
                </div>
                <div class="flex gap-2">
                    <a :href="route('clinical.treatment-plans.pdf', plan.id)" target="_blank"
                        class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">📄 PDF</a>
                    <button v-if="canApprove && plan.status === 'quoted'" @click="doApprove"
                        class="px-3 py-1.5 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700">✓ Duyệt</button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Tooth chart + add item -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Dental chart -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Sơ đồ răng</h3>
                        <ToothChart
                            v-model="selectedTeeth"
                            :treated-teeth="treatedTeethList"
                            @select="onTeethSelect"
                        />
                    </div>

                    <!-- Add item form -->
                    <div v-if="plan.is_editable && can('treatment_plans.edit')" class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Thêm dịch vụ điều trị</h3>
                        <div class="flex gap-3 flex-wrap items-end">
                            <div class="flex-1 min-w-40">
                                <select v-model="addForm.service_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                    <option value="">-- Chọn dịch vụ --</option>
                                    <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }} ({{ formatVnd(s.selling_price) }})</option>
                                </select>
                            </div>
                            <div class="w-16">
                                <input v-model="addForm.quantity" type="number" min="1" placeholder="SL" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                            </div>
                            <div class="w-24 text-xs text-gray-500 py-2">
                                Răng: {{ selectedTeeth.length ? selectedTeeth.join(', ') : '—' }}
                            </div>
                            <button @click="submitAddItem" :disabled="addForm.processing"
                                class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                                Thêm
                            </button>
                        </div>
                    </div>

                    <!-- Items table -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Dịch vụ</th>
                                    <th class="px-4 py-3 text-left font-medium">Răng</th>
                                    <th class="px-4 py-3 text-center font-medium">SL</th>
                                    <th class="px-4 py-3 text-right font-medium">Đơn giá</th>
                                    <th class="px-4 py-3 text-right font-medium">Thành tiền</th>
                                    <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                                    <th v-if="plan.is_editable || plan.status === 'in_progress'" class="px-4 py-3 text-right font-medium">TT</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="items.length === 0">
                                    <td colspan="7" class="text-center py-6 text-gray-400">Chưa có dịch vụ nào</td>
                                </tr>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ item.service_name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ item.tooth_number ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ item.quantity }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ formatVnd(item.unit_price) }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-800">{{ formatVnd(item.subtotal) }}</td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :color="item.status_color">{{ item.status_label }}</StatusBadge>
                                    </td>
                                    <td v-if="plan.is_editable || plan.status === 'in_progress'" class="px-4 py-3 text-right">
                                        <button v-if="item.status !== 'completed' && plan.status === 'in_progress'"
                                            @click="completeItem(item.id)"
                                            class="text-green-600 hover:text-green-800 text-xs font-medium mr-2">Xong</button>
                                        <button v-if="plan.is_editable"
                                            @click="removeItem(item.id)"
                                            class="text-red-500 hover:text-red-700 text-xs">Xóa</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Summary + Transitions -->
                <div class="space-y-4">
                    <!-- Totals -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700">Tổng kết</h3>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Tổng dịch vụ</span>
                                <span class="font-medium">{{ formatVnd(plan.total_amount) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Giảm giá</span>
                                <span class="text-red-600">-{{ formatVnd(plan.discount_amount) }}</span>
                            </div>
                            <div class="flex justify-between border-t pt-2 font-bold">
                                <span>Thực thu</span>
                                <span class="text-primary-700">{{ formatVnd(plan.net_total) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>Đặt cọc</span>
                                <span>{{ formatVnd(plan.deposit_amount) }}</span>
                            </div>
                        </div>

                        <!-- Update discount/deposit (draft/quoted) -->
                        <div v-if="plan.is_editable" class="pt-2 space-y-2">
                            <div class="flex gap-2 items-center">
                                <label class="text-xs text-gray-500 w-20">Giảm giá (₫)</label>
                                <input v-model="updateForm.discount_amount" type="number" min="0"
                                    class="flex-1 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-primary-500 focus:outline-none" />
                            </div>
                            <div class="flex gap-2 items-center">
                                <label class="text-xs text-gray-500 w-20">Đặt cọc (₫)</label>
                                <input v-model="updateForm.deposit_amount" type="number" min="0"
                                    class="flex-1 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-primary-500 focus:outline-none" />
                            </div>
                            <button @click="saveFinancials" :disabled="updateForm.processing"
                                class="w-full py-1.5 text-xs bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200">
                                Lưu
                            </button>
                        </div>
                    </div>

                    <!-- Status transitions -->
                    <div v-if="transitions.length > 0" class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Trạng thái</h3>
                        <div class="space-y-2">
                            <button v-for="t in transitions" :key="t.value"
                                @click="doTransition(t.value)"
                                class="w-full px-3 py-2 text-xs text-left bg-white border border-gray-300 rounded-lg hover:bg-primary-50 hover:border-primary-300 transition-colors">
                                → {{ t.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Approved date -->
                    <div v-if="plan.approved_at" class="bg-teal-50 border border-teal-200 rounded-xl p-3 text-xs text-teal-700">
                        ✓ Đã duyệt lúc {{ plan.approved_at }}
                    </div>

                    <!-- Payment schedule -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-gray-700">Lịch thanh toán</h3>
                            <button v-if="!isScheduleLocked" @click="showScheduleForm = !showScheduleForm"
                                class="text-xs text-primary-600 hover:underline">+ Thêm đợt</button>
                        </div>
                        <div v-if="showScheduleForm" class="mb-3 space-y-1.5 border-b pb-3">
                            <div class="flex gap-2">
                                <input v-model="newInst.due_date" type="date"
                                    class="flex-1 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-primary-500 focus:outline-none" />
                                <input v-model="newInst.amount" type="number" min="0" placeholder="Số tiền ₫"
                                    class="flex-1 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-primary-500 focus:outline-none" />
                            </div>
                            <input v-model="newInst.note" type="text" placeholder="Ghi chú..."
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-primary-500 focus:outline-none" />
                            <button @click="addInstallment"
                                class="w-full py-1 text-xs bg-primary-600 text-white rounded hover:bg-primary-700">Thêm đợt</button>
                        </div>
                        <div v-if="schedule.length === 0" class="text-xs text-gray-400">Chưa có lịch thanh toán</div>
                        <div v-else class="space-y-2">
                            <div v-for="(inst, idx) in schedule" :key="idx"
                                class="flex items-start justify-between text-xs">
                                <div>
                                    <p class="text-gray-600 font-medium">{{ inst.due_date }}</p>
                                    <p v-if="inst.note" class="text-gray-400">{{ inst.note }}</p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-semibold text-primary-700">{{ formatVnd(inst.amount) }}</span>
                                    <button v-if="!isScheduleLocked" @click="removeInstallment(idx)"
                                        class="text-gray-300 hover:text-red-500">✕</button>
                                </div>
                            </div>
                            <div class="border-t pt-1.5 flex justify-between text-xs font-bold">
                                <span class="text-gray-600">Tổng</span>
                                <span class="text-primary-700">{{ formatVnd(scheduleTotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import ToothChart from '@/Components/Shared/ToothChart.vue';
import { usePermission } from '@/composables/usePermission';
import { useCurrency } from '@/composables/useCurrency';

const { hasPermission: can } = usePermission();
const { formatVnd } = useCurrency();
const props = defineProps({ plan: Object, items: Array, services: Array, priceLists: Array, transitions: Array, canApprove: Boolean });

const selectedTeeth = ref([]);
const treatedTeethList = props.items.filter(i => i.tooth_number).map(i => i.tooth_number);

const addForm    = useForm({ service_id: '', quantity: 1, tooth_number: '' });
const updateForm = useForm({ discount_amount: props.plan.discount_amount, deposit_amount: props.plan.deposit_amount });

// Payment schedule
const schedule         = ref(props.plan.payment_schedule ?? []);
const showScheduleForm = ref(false);
const newInst          = ref({ due_date: '', amount: '', note: '' });
const isScheduleLocked = computed(() => ['completed', 'cancelled'].includes(props.plan.status));
const scheduleTotal    = computed(() => schedule.value.reduce((s, i) => s + (parseInt(i.amount) || 0), 0));

function addInstallment() {
    if (!newInst.value.due_date || !newInst.value.amount) return;
    schedule.value.push({ due_date: newInst.value.due_date, amount: parseInt(newInst.value.amount), note: newInst.value.note });
    newInst.value = { due_date: '', amount: '', note: '' };
    showScheduleForm.value = false;
    saveSchedule();
}

function removeInstallment(idx) {
    schedule.value.splice(idx, 1);
    saveSchedule();
}

function saveSchedule() {
    router.patch(route('clinical.treatment-plans.payment-schedule', props.plan.id), { schedule: schedule.value }, { preserveState: true });
}

function onTeethSelect(teeth) {
    selectedTeeth.value = teeth;
    addForm.tooth_number = teeth.join(',');
}

function submitAddItem() {
    addForm.post(route('clinical.treatment-plans.items.store', props.plan.id), {
        onSuccess: () => { addForm.reset('service_id'); },
    });
}

function removeItem(id) {
    router.delete(route('clinical.treatment-plan-items.destroy', id));
}

function completeItem(id) {
    router.post(route('clinical.treatment-plan-items.complete', id));
}

function doTransition(status) {
    router.post(route('clinical.treatment-plans.transition', props.plan.id), { status });
}

function doApprove() {
    router.post(route('clinical.treatment-plans.approve', props.plan.id));
}

function saveFinancials() {
    updateForm.put(route('clinical.treatment-plans.update', props.plan.id));
}
</script>
