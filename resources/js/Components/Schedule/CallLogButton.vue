<template>
    <div class="inline-block" ref="root">
        <button ref="trigger" type="button" @click.prevent.stop="toggle"
            :title="titleText"
            :class="['flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium border transition-colors whitespace-nowrap', badgeClass]">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            {{ buttonLabel }}
            <span v-if="appointment.call_count > 1" class="opacity-70">×{{ appointment.call_count }}</span>
        </button>

        <!-- Teleport ra body: dòng lịch hẹn nằm trong vùng cuộn, panel absolute sẽ bị cắt. -->
        <Teleport to="body">
            <div v-if="open" ref="panel" @click.stop
                class="fixed z-50 w-72 rounded-xl border border-gray-200 bg-white shadow-xl"
                :style="{ top: pos.top + 'px', left: pos.left + 'px' }">
                <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
                    <p class="text-xs font-semibold text-gray-700">Gọi nhắc lịch</p>
                    <span v-if="appointment.patient_phone" class="font-mono text-[11px] text-gray-400">{{ appointment.patient_phone }}</span>
                </div>

                <!-- Ghi nhận cuộc gọi: bấm thẳng vào phương án là lưu luôn, không cần bước xác nhận -->
                <div v-if="canManage" class="border-b border-gray-100 px-3 py-2">
                    <p class="mb-1.5 text-[10px] uppercase tracking-wide text-gray-400">Ghi nhận cuộc gọi</p>
                    <div class="space-y-1">
                        <button v-for="(o, i) in OUTCOMES" :key="o.value" type="button"
                            :disabled="busy"
                            @click.prevent="submit(o.value)"
                            :class="['flex w-full items-center gap-2 rounded-lg border px-2 py-2 text-left text-xs font-medium transition-colors disabled:opacity-50', o.active]">
                            <span class="flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full bg-white/70 text-[10px] font-bold">{{ i + 1 }}</span>
                            {{ o.label }}
                        </button>
                    </div>
                    <input v-model="note" type="text" maxlength="500" placeholder="Ghi chú (không bắt buộc) — gõ trước khi chọn"
                        class="mt-2 w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs focus:border-indigo-300 focus:ring-0" />
                    <p v-if="error" class="mt-1 text-[11px] text-red-600">{{ error }}</p>
                    <p v-if="busy" class="mt-1 text-[11px] text-gray-400">Đang lưu...</p>
                </div>

                <!-- Lịch sử: bằng chứng đã gọi ngày nào, ai gọi -->
                <div class="max-h-56 overflow-y-auto px-3 py-2">
                    <p class="mb-1.5 text-[10px] uppercase tracking-wide text-gray-400">Lịch sử gọi</p>
                    <p v-if="loadingLogs" class="py-2 text-center text-[11px] text-gray-400">Đang tải...</p>
                    <p v-else-if="!logs.length" class="py-2 text-center text-[11px] text-gray-400">Chưa gọi lần nào</p>
                    <ul v-else class="space-y-1.5">
                        <li v-for="l in logs" :key="l.id" class="rounded-lg bg-gray-50 px-2 py-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <span :class="['text-[11px] font-semibold', TEXT_COLORS[l.outcome_color]]">{{ l.outcome_label }}</span>
                                <span class="text-[10px] text-gray-400">{{ l.called_at }}</span>
                            </div>
                            <p class="text-[10px] text-gray-500">{{ l.by }}<span v-if="l.phone"> · {{ l.phone }}</span></p>
                            <p v-if="l.note" class="mt-0.5 text-[11px] text-gray-600">{{ l.note }}</p>
                        </li>
                    </ul>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { usePermission } from '@/composables/usePermission';

const props = defineProps({
    appointment: { type: Object, required: true },
});

// Trang cha cập nhật dòng tương ứng bằng DTO trả về — không nạp lại cả bảng.
const emit = defineEmits(['logged']);

const PANEL_WIDTH = 288; // w-72

const OUTCOMES = [
    { value: 'answered', label: 'Đã gọi — bệnh nhân nghe máy', active: 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' },
    { value: 'no_answer', label: 'Đã gọi — không nghe máy', active: 'border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100' },
    { value: 'busy', label: 'Đã gọi — máy bận', active: 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' },
    { value: 'unreachable', label: 'Không liên lạc được', active: 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100' },
];

const TEXT_COLORS = {
    green: 'text-emerald-700',
    orange: 'text-orange-700',
    yellow: 'text-amber-700',
    red: 'text-red-700',
};

const BADGE_CLASSES = {
    answered: 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
    no_answer: 'border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100',
    busy: 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100',
    unreachable: 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100',
};

const { hasPermission } = usePermission();
const canManage = computed(() => hasPermission('appointments.manage'));

const open = ref(false);
const busy = ref(false);
const error = ref('');
const note = ref('');
const logs = ref([]);
const loadingLogs = ref(false);
const root = ref(null);
const trigger = ref(null);
const panel = ref(null);
const pos = ref({ top: 0, left: 0 });

const buttonLabel = computed(() => props.appointment.last_call_outcome_label ?? 'Chưa gọi');

const badgeClass = computed(() => BADGE_CLASSES[props.appointment.last_call_outcome]
    ?? 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50');

const titleText = computed(() => props.appointment.last_call_at
    ? `Gọi lần cuối: ${props.appointment.last_call_at} — ${props.appointment.last_call_outcome_label}`
    : 'Chưa gọi điện cho bệnh nhân');

function place() {
    if (!trigger.value) return;
    const r = trigger.value.getBoundingClientRect();
    const height = panel.value?.offsetHeight ?? 260;
    const left = Math.max(8, Math.min(r.right - PANEL_WIDTH, window.innerWidth - PANEL_WIDTH - 8));
    const top = r.bottom + height + 8 > window.innerHeight
        ? Math.max(8, r.top - height - 4)
        : r.bottom + 4;
    pos.value = { top, left };
}

async function toggle() {
    open.value = !open.value;
    if (!open.value) return;
    error.value = '';
    place();
    await nextTick();
    place(); // đo lại khi panel đã có chiều cao thật
    loadLogs();
}

async function loadLogs() {
    loadingLogs.value = true;
    try {
        const res = await fetch(route('schedule.appointments.calls', props.appointment.id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });
        logs.value = await res.json();
    } catch {
        logs.value = [];
    } finally {
        loadingLogs.value = false;
    }
}

async function submit(picked) {
    if (busy.value || !picked) return;
    busy.value = true;
    error.value = '';
    try {
        const res = await fetch(route('schedule.appointments.log-call', props.appointment.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ outcome: picked, note: note.value || null }),
        });
        if (!res.ok) throw new Error();
        const data = await res.json();
        emit('logged', data.appointment);
        note.value = '';
        await loadLogs();
    } catch {
        error.value = 'Không lưu được cuộc gọi, thử lại.';
    } finally {
        busy.value = false;
    }
}

function onOutside(e) {
    if (open.value && root.value && !root.value.contains(e.target) && !panel.value?.contains(e.target)) {
        open.value = false;
    }
}
function onViewportChange() {
    if (open.value) open.value = false;
}

onMounted(() => {
    document.addEventListener('click', onOutside);
    window.addEventListener('scroll', onViewportChange, true);
    window.addEventListener('resize', onViewportChange);
});
onUnmounted(() => {
    document.removeEventListener('click', onOutside);
    window.removeEventListener('scroll', onViewportChange, true);
    window.removeEventListener('resize', onViewportChange);
});
</script>
