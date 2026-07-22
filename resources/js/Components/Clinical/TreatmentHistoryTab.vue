<template>
    <div class="space-y-4">

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

        <div v-else class="p-3 space-y-3">
            <div v-for="(plan, pi) in treatmentPlans" :key="plan.id"
                :class="['group rounded-lg overflow-hidden',
                    plan.status === 'draft'
                        ? 'border-2 border-dashed border-gray-300'
                        : 'border border-gray-200 border-l-4 border-l-indigo-300']">
                <!-- Plan header row -->
                <div
                    :class="['flex items-center gap-3 px-4 py-3 cursor-pointer transition-colors',
                        pendingKey('App\\Models\\TreatmentPlan', plan.id) ? 'bg-red-50/60 hover:bg-red-100/40'
                            : plan.status === 'draft' ? 'bg-gray-100/70 hover:bg-gray-200/60'
                            : 'bg-gray-50/60 hover:bg-indigo-50/40']"
                    @click="togglePlan(plan.id)">
                    <svg :class="['w-3.5 h-3.5 text-gray-400 transition-transform flex-shrink-0', expanded.has(plan.id) ? 'rotate-90' : '']"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-xs text-gray-500 font-mono w-8 flex-shrink-0">{{ pi + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p :class="['text-base font-semibold leading-snug', plan.status === 'draft' ? 'text-gray-500 italic' : 'text-gray-900']">
                            {{ planItemsSummary(plan) || 'Chưa có dịch vụ' }}
                        </p>
                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ plan.code }}</p>
                    </div>
                    <span class="text-xs text-gray-500 hidden sm:block">{{ plan.doctor }}</span>
                    <div class="relative flex-shrink-0" @click.stop>
                        <button
                            :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold transition-opacity',
                                stageClass(plan.status),
                                plan.transitions?.length ? 'cursor-pointer hover:opacity-80' : 'cursor-default']"
                            @click="plan.transitions?.length ? toggleDropdown(plan.id) : null">
                            {{ stageLabel(plan.status) }}
                            <svg v-if="plan.transitions?.length" class="w-2.5 h-2.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div v-if="openDropdown === plan.id"
                            class="absolute right-0 top-full mt-1 z-30 bg-white border border-gray-200 rounded-xl shadow-lg py-1 min-w-[160px]">
                            <p class="px-3 py-1.5 text-[10px] text-gray-400 uppercase tracking-wide font-medium border-b border-gray-100">Chuyển sang</p>
                            <button v-for="t in plan.transitions" :key="t.value"
                                @click="doTransition(plan.id, t.value)"
                                class="w-full text-left px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <span :class="['w-2 h-2 rounded-full flex-shrink-0', stageDot(t.value)]"></span>
                                {{ stageLabel(t.value) }}
                            </button>
                        </div>
                    </div>
                    <div class="hidden md:flex items-center gap-3 text-xs ml-2">
                        <span class="text-gray-500">Tổng: <span class="font-semibold text-gray-800">{{ fmt(planGrossTotal(plan)) }}</span></span>
                        <span v-if="planDiscountTotal(plan) > 0" class="text-gray-500">Giảm giá: <span class="font-semibold text-rose-600">-{{ fmt(planDiscountTotal(plan)) }}</span></span>
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
                            class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors hidden sm:inline-flex"
                            @click.stop>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Xem
                        </Link>
                        <button v-if="!plan.amount_paid" @click.stop="openDeletePlan(plan)"
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
                        <div @click.stop>
                            <p class="text-gray-400 uppercase tracking-wide font-medium mb-0.5">Ngày điều trị</p>
                            <div v-if="!dateEditOpen[plan.id]" class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-gray-800 font-medium">{{ formatDisplayDate(dateEdits[plan.id]) || '—' }}</span>
                                <button @click="openDateEdit(plan.id)" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Sửa ngày điều trị">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                                    </svg>
                                </button>
                            </div>
                            <div v-else>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <input type="date" :ref="el => setDateInputRef(plan.id, el)" :value="datePartOf(dateEdits[plan.id])"
                                        @change="setDatePart(plan.id, $event.target.value)"
                                        class="flex-1 min-w-0 border border-gray-300 rounded px-1.5 py-0.5 text-xs focus:ring-1 focus:ring-indigo-400 focus:outline-none" />
                                    <input type="time" lang="vi" :value="timePartOf(dateEdits[plan.id])"
                                        @change="setTimePart(plan.id, $event.target.value)"
                                        class="w-20 border border-gray-300 rounded px-1.5 py-0.5 text-xs focus:ring-1 focus:ring-indigo-400 focus:outline-none" />
                                    <button @click="saveDate(plan.id)" :disabled="!dateEdits[plan.id]"
                                        class="px-2 py-0.5 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">
                                        Lưu
                                    </button>
                                    <button @click="dateEditOpen[plan.id] = false" class="text-gray-400 hover:text-gray-600 transition-colors" title="Hủy">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <p v-if="!dateEdits[plan.id]" class="text-red-500 mt-1">Ngày điều trị là bắt buộc.</p>
                            </div>
                        </div>
                        <div v-if="plan.expected_end_date">
                            <p class="text-gray-400 uppercase tracking-wide font-medium mb-0.5">Hoàn thành dự kiến</p>
                            <p class="text-gray-800 font-medium">{{ plan.expected_end_date }}</p>
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
                                <th class="px-4 py-2 text-right text-gray-500 font-medium">Giảm giá</th>
                                <th class="px-4 py-2 text-right text-gray-500 font-medium">Thành tiền</th>
                                <th class="px-4 py-2 text-center text-gray-500 font-medium">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="plan.items.length === 0">
                                <td colspan="8" class="px-4 py-4 text-center text-gray-400">Chưa có dịch vụ</td>
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
                                <td class="px-4 py-2 text-right tabular-nums">
                                    <span v-if="item.discount" class="text-rose-500">-{{ fmt(item.discount) }}</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums font-semibold text-gray-800">{{ fmt(item.unit_price * item.quantity - (item.discount ?? 0)) }}</td>
                                <td class="px-4 py-2 text-center">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', itemStatusClass(item.status)]">
                                        {{ item.status_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="plan.items.length > 0" class="bg-gray-50 border-t border-gray-200">
                                <td colspan="6" class="px-4 py-2 text-right text-gray-500 text-xs font-medium">Tổng kế hoạch:</td>
                                <td class="px-4 py-2 text-right tabular-nums font-bold text-gray-900">{{ fmt(planNetTotal(plan)) }}</td>
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
import { ref, reactive, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DeleteConfirmModal from '@/Components/DeleteConfirmModal.vue';

const props = defineProps({
    treatmentPlans:   { type: Array,  default: () => [] },
    pendingDeletions: { type: Object, default: () => ({}) },
});

// Countdown ticker
const now = ref(Date.now());
let timer = null;
onMounted(() => {
    timer = setInterval(() => { now.value = Date.now(); }, 1000);
    document.addEventListener('click', closeDropdown);
});
onUnmounted(() => {
    clearInterval(timer);
    document.removeEventListener('click', closeDropdown);
});

// Status transition dropdown
const openDropdown = ref(null);
function toggleDropdown(planId) { openDropdown.value = openDropdown.value === planId ? null : planId; }
function closeDropdown() { openDropdown.value = null; }
function doTransition(planId, status) {
    openDropdown.value = null;
    router.post(route('clinical.treatment-plans.transition', planId), { status }, { preserveScroll: true });
}

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

// ── Ngày điều trị inline edit ───────────────────────────────────────────────
const dateEdits = reactive(
    Object.fromEntries(props.treatmentPlans.map(p => [p.id, p.start_date_raw ?? '']))
);
const dateEditOpen = reactive(
    Object.fromEntries(props.treatmentPlans.map(p => [p.id, false]))
);

function formatDisplayDate(raw) {
    if (!raw) return '';
    const [datePart, timePart] = raw.split('T');
    const [y, m, d] = datePart.split('-');
    if (!y || !m || !d) return raw;
    return timePart ? `${d}/${m}/${y} ${timePart}` : `${d}/${m}/${y}`;
}

function datePartOf(raw) {
    return raw ? raw.split('T')[0] ?? '' : '';
}
function timePartOf(raw) {
    return raw ? raw.split('T')[1] ?? '00:00' : '00:00';
}
function setDatePart(planId, v) {
    const time = timePartOf(dateEdits[planId]);
    dateEdits[planId] = v ? `${v}T${time}` : '';
}
function setTimePart(planId, v) {
    const date = datePartOf(dateEdits[planId]);
    if (date) dateEdits[planId] = `${date}T${v || '00:00'}`;
}

function currentTimeStr() {
    const d = new Date();
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

function todayStr() {
    const d = new Date();
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

// Bấm "Sửa" thì bấm chuột thẳng vào ô ngày luôn, khỏi phải bấm thêm lần nữa.
const dateInputRefs = {};
function setDateInputRef(planId, el) {
    if (el) dateInputRefs[planId] = el;
}

// Luôn mặc định giờ theo thời điểm bấm sửa (không giữ giờ cũ đã lưu — thường là 00:00 "giả"
// do dữ liệu cũ chưa từng chọn giờ). Giữ nguyên ngày đang có, nếu chưa có ngày thì lấy ngày hôm nay.
function openDateEdit(planId) {
    const date = datePartOf(dateEdits[planId]) || todayStr();
    dateEdits[planId] = `${date}T${currentTimeStr()}`;
    dateEditOpen[planId] = true;
    nextTick(() => dateInputRefs[planId]?.focus());
}

function saveDate(planId) {
    if (!dateEdits[planId]) return;

    router.put(route('clinical.treatment-plans.update', planId), {
        start_date: dateEdits[planId],
        action: 'update_date',
    }, {
        preserveScroll: true,
        onSuccess: () => { dateEditOpen[planId] = false; },
    });
}

// ── Expand/collapse plans ───────────────────────────────────────────────────
const expanded = ref(new Set(props.treatmentPlans.map(p => p.id)));
function togglePlan(id) {
    if (expanded.value.has(id)) { expanded.value.delete(id); }
    else { expanded.value.add(id); }
}

// ── Formatters ──────────────────────────────────────────────────────────────
function fmt(val) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val ?? 0);
}

// Recomputed straight from each item's unit_price/quantity/discount rather than
// trusting the plan/item aggregate columns, which can go stale on old records.
function planGrossTotal(plan) {
    return (plan.items ?? []).reduce((s, i) => s + i.unit_price * i.quantity, 0);
}
function planDiscountTotal(plan) {
    return (plan.items ?? []).reduce((s, i) => s + (i.discount ?? 0), 0);
}
function planNetTotal(plan) {
    return planGrossTotal(plan) - planDiscountTotal(plan);
}
function planItemsSummary(plan) {
    return (plan.items ?? []).map(i => i.name).join(', ');
}
function stageLabel(status) {
    if (status === 'cancelled') return 'Đã hủy';
    if (status === 'completed') return 'Hoàn thành';
    if (status === 'in_progress') return 'Đang điều trị';
    if (status === 'approved') return 'Chưa điều trị';
    return 'Nháp';
}
function stageClass(status) {
    if (status === 'cancelled') return 'bg-red-100 text-red-600';
    if (status === 'completed') return 'bg-emerald-100 text-emerald-700';
    if (status === 'in_progress') return 'bg-indigo-100 text-indigo-700';
    if (status === 'approved') return 'bg-amber-100 text-amber-700';
    if (status === 'draft') return 'bg-gray-100 text-gray-500 border border-dashed border-gray-400';
    return 'bg-gray-100 text-gray-600';
}
function stageDot(status) {
    if (status === 'cancelled') return 'bg-red-500';
    if (status === 'completed') return 'bg-emerald-500';
    if (status === 'in_progress') return 'bg-indigo-500';
    if (status === 'approved') return 'bg-amber-500';
    return 'bg-gray-400';
}
function itemStatusClass(status) {
    const map = { pending: 'bg-gray-100 text-gray-600', in_progress: 'bg-amber-100 text-amber-700', completed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-red-100 text-red-600' };
    return map[status] ?? 'bg-gray-100 text-gray-600';
}
</script>
