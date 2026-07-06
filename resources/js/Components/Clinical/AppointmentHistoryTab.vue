<template>
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
                        <td class="px-4 py-2 text-right">
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
                            <div v-else class="flex items-center gap-2 justify-end">
                                <button v-if="canQuickRegister(appt)" @click="quickRegister(appt)"
                                    :disabled="quickRegisteringId === appt.id"
                                    class="text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-medium transition-colors whitespace-nowrap disabled:opacity-50">
                                    {{ quickRegisteringId === appt.id ? 'Đang xử lý...' : '📋 Đăng ký khám' }}
                                </button>
                                <button @click="openDeleteAppt(appt)"
                                    class="text-gray-300 hover:text-red-500 transition-colors flex-shrink-0"
                                    title="Xóa lịch hẹn">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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
import { router } from '@inertiajs/vue3';
import DeleteConfirmModal from '@/Components/DeleteConfirmModal.vue';
import { usePermission } from '@/composables/usePermission';

const props = defineProps({
    appointments:     { type: Array,  default: () => [] },
    pendingDeletions: { type: Object, default: () => ({}) },
});

const { hasPermission: can } = usePermission();

// ── Quick register (đăng ký khám) ────────────────────────────────────────────
const QUICK_REGISTER_STATUSES = ['booked', 'confirmed'];
const quickRegisteringId = ref(null);

function canQuickRegister(appt) {
    return can('appointments.manage') && QUICK_REGISTER_STATUSES.includes(appt.status) && !appt.has_registration;
}

function quickRegister(appt) {
    if (quickRegisteringId.value) return;
    quickRegisteringId.value = appt.id;
    router.post(route('schedule.appointments.quick-register', appt.id), {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => { quickRegisteringId.value = null; },
    });
}

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

function onModalConfirm(reason) {
    modal.show = false;
    router.delete(modal.route, { data: { reason }, preserveScroll: true });
}

// ── Undo ────────────────────────────────────────────────────────────────────
function undo(pendingId) {
    router.delete(route('pending-deletions.undo', pendingId), { preserveScroll: true });
}

// ── Formatters ──────────────────────────────────────────────────────────────
function apptStatusClass(status) {
    const map = { booked: 'bg-gray-100 text-gray-600', confirmed: 'bg-blue-100 text-blue-700', checked_in: 'bg-teal-100 text-teal-700', in_treatment: 'bg-indigo-100 text-indigo-700', completed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-red-100 text-red-600', no_show: 'bg-orange-100 text-orange-700', rescheduled: 'bg-yellow-100 text-yellow-700' };
    return map[status] ?? 'bg-gray-100 text-gray-600';
}
</script>
