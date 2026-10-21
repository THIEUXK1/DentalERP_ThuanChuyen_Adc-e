<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="$emit('cancel')"/>

            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Xóa kế hoạch {{ plan?.code }}</h3>
                        <p v-if="plan?.created_at" class="text-sm text-gray-500 mt-0.5">Ngày tạo: {{ plan.created_at }}</p>
                    </div>
                </div>

                <div v-if="loading" class="py-6 text-center text-sm text-gray-500">Đang kiểm tra dữ liệu liên quan…</div>
                <div v-else-if="loadError" class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ loadError }}</div>

                <template v-else-if="impact">
                    <!-- Lý do không thể xoá -->
                    <div v-if="impact.blockers.length" class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                        <p class="font-semibold mb-1">Không thể xóa kế hoạch này:</p>
                        <ul class="list-disc pl-5 space-y-0.5">
                            <li v-for="(b, i) in impact.blockers" :key="i">{{ b }}</li>
                        </ul>
                    </div>

                    <!-- Hậu quả -->
                    <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                        <p class="font-semibold mb-1.5">Khi xác nhận xóa, hệ thống sẽ:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li v-if="impact.payments_to_reverse.length">
                                <strong>Hoàn tác ngay {{ impact.payments_to_reverse.length }} khoản thu, tổng {{ fmt(impact.net_paid) }}</strong>
                                (ghi bút toán âm, sổ quỹ ghi chi hoàn trả cho khách):
                                <ul class="list-[circle] pl-5 mt-0.5 text-xs text-amber-800">
                                    <li v-for="p in impact.payments_to_reverse" :key="p.id">
                                        {{ p.payment_date }} · {{ p.method }} · <span class="tabular-nums">{{ fmt(p.amount) }}</span>
                                    </li>
                                </ul>
                            </li>
                            <li v-if="impact.invoices.length">
                                Xóa {{ impact.invoices.length }} hóa đơn
                                ({{ impact.invoices.map(i => i.code).join(', ') }})<span v-if="impact.invoices.some(i => i.has_debt)"> cùng công nợ</span>.
                            </li>
                            <li v-if="impact.items_count">
                                Xóa {{ impact.items_count }} dịch vụ<span v-if="impact.completed_items_count">, trong đó <strong>{{ impact.completed_items_count }} đã hoàn thành</strong></span>.
                            </li>
                            <li v-if="impact.commissions.count">
                                Xóa {{ impact.commissions.count }} khoản hoa hồng ({{ fmt(impact.commissions.amount) }}).
                            </li>
                            <li v-if="impact.kpis.count">
                                Xóa {{ impact.kpis.count }} khoản KPI bác sĩ/phụ tá ({{ fmt(impact.kpis.amount) }}).
                            </li>
                            <li v-if="impact.lab_orders_count">
                                {{ impact.lab_orders_count }} phiếu labo sẽ không còn gắn với kế hoạch.
                            </li>
                            <li v-if="impact.consent_forms_count">
                                {{ impact.consent_forms_count }} phiếu cam kết sẽ không còn gắn với kế hoạch.
                            </li>
                            <li>Xóa vĩnh viễn sau <strong>10 phút</strong>. Trong thời gian đó có thể hoàn tác xóa<span v-if="impact.payments_to_reverse.length"> — các khoản thu sẽ được <strong>khôi phục nguyên trạng</strong> (gỡ bút toán hoàn tiền)</span>.</li>
                        </ul>
                    </div>

                    <template v-if="!impact.blockers.length">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Lý do xóa <span class="text-red-500">*</span>
                        </label>
                        <textarea v-model="reason" ref="textareaRef" rows="3" placeholder="Nhập lý do xóa..."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent resize-none"
                            :class="{ 'border-red-400 ring-1 ring-red-400': submitted && !reason.trim() }"/>
                        <p v-if="submitted && !reason.trim()" class="text-xs text-red-500 mt-1">Vui lòng nhập lý do xóa.</p>

                        <label v-if="impact.payments_to_reverse.length" class="mt-3 flex items-start gap-2 text-sm text-gray-700 cursor-pointer select-none">
                            <input v-model="acknowledged" type="checkbox" class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                            <span>Tôi hiểu {{ fmt(impact.net_paid) }} đã thu sẽ bị hoàn tác.</span>
                        </label>
                    </template>
                </template>

                <p v-if="error" class="text-xs text-red-500 mt-2">{{ error }}</p>

                <div class="flex justify-end gap-3 mt-5">
                    <button @click="$emit('cancel')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        {{ canConfirm || loading ? 'Hủy bỏ' : 'Đóng' }}
                    </button>
                    <button v-if="impact && !impact.blockers.length" @click="confirm" :disabled="!canConfirm || processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ processing ? 'Đang xóa…' : 'Xác nhận xóa' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    show:       { type: Boolean, default: false },
    plan:       { type: Object,  default: null },
    error:      { type: String,  default: '' },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);

const impact       = ref(null);
const loading      = ref(false);
const loadError    = ref('');
const reason       = ref('');
const acknowledged = ref(false);
const submitted    = ref(false);
const textareaRef  = ref(null);

// Phải tích xác nhận khi có tiền bị hoàn tác — tránh bấm nhầm làm sổ quỹ ghi chi.
const canConfirm = computed(() => impact.value
    && !impact.value.blockers.length
    && (!impact.value.payments_to_reverse.length || acknowledged.value));

watch(() => props.show, async (val) => {
    if (!val || !props.plan) return;
    impact.value = null;
    loadError.value = '';
    reason.value = '';
    acknowledged.value = false;
    submitted.value = false;
    loading.value = true;
    try {
        const { data } = await axios.get(route('clinical.treatment-plans.delete-impact', props.plan.id));
        impact.value = data;
        nextTick(() => textareaRef.value?.focus());
    } catch (e) {
        loadError.value = e.response?.data?.message ?? 'Không tải được thông tin kế hoạch.';
    } finally {
        loading.value = false;
    }
});

function confirm() {
    submitted.value = true;
    if (!reason.value.trim() || !canConfirm.value) return;
    emit('confirm', reason.value.trim());
}

function fmt(v) {
    return new Intl.NumberFormat('vi-VN').format(v ?? 0) + ' đ';
}
</script>
