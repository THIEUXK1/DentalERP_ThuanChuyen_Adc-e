<template>
    <div class="space-y-4">

    <!-- ── Lịch hẹn ──────────────────────────────────────────────────────── -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Lịch hẹn liên quan
            </h3>
            <span class="text-xs text-gray-400">{{ appointments.length }} lịch hẹn</span>
        </div>

        <div v-if="appointments.length === 0" class="flex flex-col items-center justify-center py-8 text-gray-400">
            <svg class="w-8 h-8 mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm">Chưa có lịch hẹn</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-2 text-left text-gray-500 font-medium w-8">#</th>
                        <th class="px-4 py-2 text-left text-gray-500 font-medium">Mã lịch</th>
                        <th class="px-4 py-2 text-left text-gray-500 font-medium">Ngày giờ hẹn</th>
                        <th class="px-4 py-2 text-left text-gray-500 font-medium hidden sm:table-cell">Bác sĩ</th>
                        <th class="px-4 py-2 text-left text-gray-500 font-medium hidden md:table-cell">Dịch vụ</th>
                        <th class="px-4 py-2 text-left text-gray-500 font-medium hidden md:table-cell">Thời lượng</th>
                        <th class="px-4 py-2 text-center text-gray-500 font-medium">Trạng thái</th>
                        <th class="px-4 py-2 text-left text-gray-500 font-medium hidden lg:table-cell">Ghi chú</th>
                        <th class="px-4 py-2 w-8"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="(appt, idx) in appointments" :key="appt.id"
                        :class="['transition-colors', pendingKey('App\\Models\\Appointment', appt.id) ? 'bg-red-50/60' : 'hover:bg-sky-50/30']">
                        <td class="px-4 py-2 text-gray-400">{{ idx + 1 }}</td>
                        <td class="px-4 py-2 font-mono text-gray-700 font-medium">{{ appt.code }}</td>
                        <td class="px-4 py-2">
                            <p class="font-medium text-gray-800">{{ appt.scheduled_date }}</p>
                            <p class="text-gray-400">{{ appt.scheduled_time }}</p>
                        </td>
                        <td class="px-4 py-2 text-gray-600 hidden sm:table-cell">{{ appt.doctor }}</td>
                        <td class="px-4 py-2 text-gray-600 hidden md:table-cell">{{ appt.service }}</td>
                        <td class="px-4 py-2 text-gray-500 hidden md:table-cell">{{ appt.duration_minutes ? appt.duration_minutes + ' phút' : '—' }}</td>
                        <td class="px-4 py-2 text-center">
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', apptStatusClass(appt.status)]">
                                {{ appt.status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-400 hidden lg:table-cell max-w-xs truncate">{{ appt.notes || '—' }}</td>
                        <td class="px-4 py-2 text-center">
                            <!-- Pending deletion: show countdown + undo -->
                            <template v-if="pendingKey('App\\Models\\Appointment', appt.id)">
                                <div class="flex items-center gap-1.5 justify-end">
                                    <span class="text-xs text-red-500 font-medium whitespace-nowrap">
                                        Xóa sau {{ countdown(pendingKey('App\\Models\\Appointment', appt.id).execute_at) }}
                                    </span>
                                    <button @click="undo(pendingKey('App\\Models\\Appointment', appt.id).id)"
                                        class="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-700 hover:bg-amber-200 font-medium transition-colors whitespace-nowrap">
                                        Hoàn tác
                                    </button>
                                </div>
                            </template>
                            <!-- Normal: trash button -->
                            <template v-else>
                                <button @click="openDeleteAppt(appt)"
                                    class="text-gray-300 hover:text-red-500 transition-colors"
                                    title="Xóa lịch hẹn">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Kế hoạch điều trị ──────────────────────────────────────────────── -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Nội dung / Điều trị
            </h3>
            <span class="text-xs text-gray-400">{{ treatmentPlans.length }} kế hoạch</span>
        </div>

        <div v-if="treatmentPlans.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-400">
            <svg class="w-10 h-10 mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm">Chưa có kế hoạch điều trị</p>
        </div>

        <div v-else class="divide-y divide-gray-100">
            <div v-for="(plan, pi) in treatmentPlans" :key="plan.id" class="group">
                <!-- Plan header row -->
                <div
                    :class="['flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors',
                        pendingKey('App\\Models\\TreatmentPlan', plan.id) ? 'bg-red-50/60 hover:bg-red-100/40' : 'bg-gray-50/60 hover:bg-indigo-50/40']"
                    @click="togglePlan(plan.id)">
                    <svg :class="['w-3.5 h-3.5 text-gray-400 transition-transform flex-shrink-0', expanded.has(plan.id) ? 'rotate-90' : '']"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-xs text-gray-500 font-mono w-8 flex-shrink-0">{{ pi + 1 }}</span>
                    <span class="text-xs text-gray-500 w-28 flex-shrink-0">{{ plan.created_at }}</span>
                    <span class="text-sm font-medium text-gray-800 flex-1 truncate">{{ plan.code }}</span>
                    <span class="text-xs text-gray-500 hidden sm:block">{{ plan.doctor }}</span>
                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', statusClass(plan.status)]">
                        {{ plan.status_label }}
                    </span>
                    <div class="hidden md:flex items-center gap-3 text-xs ml-2">
                        <span class="text-gray-500">Tổng: <span class="font-semibold text-gray-800">{{ fmt(plan.total_amount) }}</span></span>
                        <span class="text-gray-500">Đã thu: <span class="font-semibold text-emerald-600">{{ fmt(plan.amount_paid) }}</span></span>
                        <span v-if="plan.amount_due > 0" class="text-gray-500">Nợ: <span class="font-semibold text-rose-600">{{ fmt(plan.amount_due) }}</span></span>
                    </div>

                    <!-- Pending deletion row: countdown + undo -->
                    <template v-if="pendingKey('App\\Models\\TreatmentPlan', plan.id)">
                        <div class="flex items-center gap-1.5 flex-shrink-0" @click.stop>
                            <span class="text-xs text-red-500 font-medium whitespace-nowrap">
                                Xóa sau {{ countdown(pendingKey('App\\Models\\TreatmentPlan', plan.id).execute_at) }}
                            </span>
                            <button @click="undo(pendingKey('App\\Models\\TreatmentPlan', plan.id).id)"
                                class="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-700 hover:bg-amber-200 font-medium transition-colors whitespace-nowrap">
                                Hoàn tác
                            </button>
                        </div>
                    </template>
                    <!-- Normal: link + trash -->
                    <template v-else>
                        <Link :href="route('clinical.treatment-plans.show', plan.id)"
                            class="flex-shrink-0 text-indigo-500 hover:text-indigo-700 text-xs hidden sm:flex items-center gap-1"
                            @click.stop>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Xem
                        </Link>
                        <button @click.stop="openDeletePlan(plan)"
                            class="flex-shrink-0 text-gray-300 hover:text-red-500 transition-colors"
                            title="Xóa kế hoạch">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </template>
                </div>

                <!-- Expanded content -->
                <div v-if="expanded.has(plan.id)">
                    <div class="px-4 py-3 bg-indigo-50/40 border-b border-indigo-100 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-2 text-xs">
                        <div v-if="plan.chief_complaint">
                            <p class="text-gray-400 uppercase tracking-wide font-medium mb-0.5">Lý do khám</p>
                            <p class="text-gray-800 font-medium">{{ plan.chief_complaint }}</p>
                        </div>
                        <div v-if="plan.diagnosis">
                            <p class="text-gray-400 uppercase tracking-wide font-medium mb-0.5">Chẩn đoán</p>
                            <p class="text-gray-800 font-medium">{{ plan.diagnosis }}</p>
                        </div>
                        <div v-if="plan.treatment_goal">
                            <p class="text-gray-400 uppercase tracking-wide font-medium mb-0.5">Mục tiêu</p>
                            <p class="text-gray-800 font-medium">{{ plan.treatment_goal }}</p>
                        </div>
                        <div v-if="plan.priority">
                            <p class="text-gray-400 uppercase tracking-wide font-medium mb-0.5">Ưu tiên</p>
                            <span :class="['inline-flex px-2 py-0.5 rounded-full font-medium', priorityClass(plan.priority)]">
                                {{ priorityLabel(plan.priority) }}
                            </span>
                        </div>
                        <div v-if="plan.start_date">
                            <p class="text-gray-400 uppercase tracking-wide font-medium mb-0.5">Bắt đầu dự kiến</p>
                            <p class="text-gray-800 font-medium">{{ plan.start_date }}</p>
                        </div>
                        <div v-if="plan.expected_end_date">
                            <p class="text-gray-400 uppercase tracking-wide font-medium mb-0.5">Hoàn thành dự kiến</p>
                            <p class="text-gray-800 font-medium">{{ plan.expected_end_date }}</p>
                        </div>
                        <div v-if="plan.estimated_sessions">
                            <p class="text-gray-400 uppercase tracking-wide font-medium mb-0.5">Số buổi</p>
                            <p class="text-gray-800 font-medium">{{ plan.estimated_sessions }} buổi</p>
                        </div>
                        <div v-if="plan.frequency">
                            <p class="text-gray-400 uppercase tracking-wide font-medium mb-0.5">Tần suất</p>
                            <p class="text-gray-800 font-medium">{{ plan.frequency }}</p>
                        </div>
                        <div v-if="plan.notes" class="col-span-2 sm:col-span-3 lg:col-span-4">
                            <p class="text-gray-400 uppercase tracking-wide font-medium mb-0.5">Ghi chú</p>
                            <p class="text-gray-700">{{ plan.notes }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-4 py-2 text-left text-gray-500 font-medium w-8">#</th>
                                <th class="px-4 py-2 text-left text-gray-500 font-medium">Dịch vụ / Thủ thuật</th>
                                <th class="px-4 py-2 text-left text-gray-500 font-medium hidden sm:table-cell">Vị trí răng</th>
                                <th class="px-4 py-2 text-right text-gray-500 font-medium">Đơn giá</th>
                                <th class="px-4 py-2 text-right text-gray-500 font-medium">SL</th>
                                <th class="px-4 py-2 text-right text-gray-500 font-medium">Thành tiền</th>
                                <th class="px-4 py-2 text-center text-gray-500 font-medium">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="plan.items.length === 0">
                                <td colspan="7" class="px-4 py-4 text-center text-gray-400">Chưa có dịch vụ</td>
                            </tr>
                            <tr v-for="(item, ii) in plan.items" :key="item.id"
                                class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-4 py-2 text-gray-400">{{ ii + 1 }}</td>
                                <td class="px-4 py-2">
                                    <p class="font-medium text-gray-800">{{ item.name }}</p>
                                    <p v-if="item.notes" class="text-gray-400 text-xs mt-0.5">{{ item.notes }}</p>
                                </td>
                                <td class="px-4 py-2 hidden sm:table-cell">
                                    <span v-if="item.tooth_number"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-mono text-xs">
                                        R{{ item.tooth_number }}
                                    </span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-600">{{ fmt(item.unit_price) }}</td>
                                <td class="px-4 py-2 text-right text-gray-600">{{ item.quantity }}</td>
                                <td class="px-4 py-2 text-right tabular-nums font-semibold text-gray-800">{{ fmt(item.subtotal) }}</td>
                                <td class="px-4 py-2 text-center">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', itemStatusClass(item.status)]">
                                        {{ item.status_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="plan.items.length > 0" class="bg-gray-50 border-t border-gray-200">
                                <td colspan="5" class="px-4 py-2 text-right text-gray-500 text-xs font-medium">Tổng kế hoạch:</td>
                                <td class="px-4 py-2 text-right tabular-nums font-bold text-gray-900">{{ fmt(plan.total_amount) }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete confirm modal -->
    <DeleteConfirmModal
        :show="modal.show"
        :title="modal.title"
        :subtitle="modal.subtitle"
        @confirm="onModalConfirm"
        @cancel="modal.show = false"
    />

    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DeleteConfirmModal from '@/Components/DeleteConfirmModal.vue';

const props = defineProps({
    treatmentPlans:   { type: Array,  default: () => [] },
    appointments:     { type: Array,  default: () => [] },
    pendingDeletions: { type: Object, default: () => ({}) },
});

// Countdown ticker
const now = ref(Date.now());
let timer = null;
onMounted(() => { timer = setInterval(() => { now.value = Date.now(); }, 1000); });
onUnmounted(() => clearInterval(timer));

// Lookup a pending deletion entry for a given model key
function pendingKey(type, id) {
    const key = type + ':' + id;
    const entry = props.pendingDeletions[key];
    if (!entry) return null;
    // if grace period already passed, treat as gone
    if (new Date(entry.execute_at).getTime() <= now.value) return null;
    return entry;
}

function countdown(isoDate) {
    const ms = new Date(isoDate).getTime() - now.value;
    if (ms <= 0) return '0:00';
    const m = Math.floor(ms / 60000);
    const s = Math.floor((ms % 60000) / 1000);
    return `${m}:${String(s).padStart(2, '0')}`;
}

// ── Delete modal ────────────────────────────────────────────────────────────
const modal = reactive({
    show: false, title: '', subtitle: '',
    route: null, id: null,
});

function openDeleteAppt(appt) {
    modal.show     = true;
    modal.title    = `Xóa lịch hẹn ${appt.code}`;
    modal.subtitle = `Ngày hẹn: ${appt.scheduled_date} ${appt.scheduled_time}`;
    modal.route    = route('schedule.appointments.destroy', appt.id);
}

function openDeletePlan(plan) {
    modal.show     = true;
    modal.title    = `Xóa kế hoạch ${plan.code}`;
    modal.subtitle = `Ngày tạo: ${plan.created_at}`;
    modal.route    = route('clinical.treatment-plans.destroy', plan.id);
}

function onModalConfirm(reason) {
    modal.show = false;
    router.delete(modal.route, { data: { reason }, preserveScroll: true });
}

// ── Undo ────────────────────────────────────────────────────────────────────
function undo(pendingId) {
    router.delete(route('pending-deletions.undo', pendingId), { preserveScroll: true });
}

// ── Expand/collapse plans ───────────────────────────────────────────────────
const expanded = ref(new Set(props.treatmentPlans.length > 0 ? [props.treatmentPlans[0].id] : []));
function togglePlan(id) {
    if (expanded.value.has(id)) { expanded.value.delete(id); }
    else { expanded.value.add(id); }
}

// ── Formatters ──────────────────────────────────────────────────────────────
function fmt(val) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val ?? 0);
}
function statusClass(status) {
    const map = { draft: 'bg-gray-100 text-gray-600', approved: 'bg-blue-100 text-blue-700', in_progress: 'bg-amber-100 text-amber-700', completed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-red-100 text-red-600' };
    return map[status] ?? 'bg-gray-100 text-gray-600';
}
function itemStatusClass(status) {
    const map = { pending: 'bg-gray-100 text-gray-600', in_progress: 'bg-amber-100 text-amber-700', completed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-red-100 text-red-600' };
    return map[status] ?? 'bg-gray-100 text-gray-600';
}
function priorityLabel(p) { return { normal: 'Bình thường', urgent: 'Cần xử lý sớm', emergency: 'Cấp cứu' }[p] ?? p; }
function priorityClass(p) { return { normal: 'bg-gray-100 text-gray-600', urgent: 'bg-amber-100 text-amber-700', emergency: 'bg-red-100 text-red-700' }[p] ?? 'bg-gray-100 text-gray-600'; }
function apptStatusClass(status) {
    const map = { booked: 'bg-gray-100 text-gray-600', confirmed: 'bg-blue-100 text-blue-700', checked_in: 'bg-teal-100 text-teal-700', in_treatment: 'bg-indigo-100 text-indigo-700', completed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-red-100 text-red-600', no_show: 'bg-orange-100 text-orange-700', rescheduled: 'bg-yellow-100 text-yellow-700' };
    return map[status] ?? 'bg-gray-100 text-gray-600';
}
</script>
