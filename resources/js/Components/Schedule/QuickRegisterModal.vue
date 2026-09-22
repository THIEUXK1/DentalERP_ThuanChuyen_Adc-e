<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg z-10 flex flex-col max-h-[90vh]">
                <div class="flex items-start justify-between px-5 pt-5 pb-4 border-b border-gray-100 flex-shrink-0">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Đăng ký khám</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ patient.full_name }}
                            <span class="font-mono text-xs text-gray-400 ml-1">{{ patient.code }}</span>
                        </p>
                    </div>
                    <button @click="$emit('close')" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                    <!-- Ngày khoá = hôm nay: server luôn chốt registration_date = today -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-600 mb-1 block">Ngày khám</label>
                            <div class="relative">
                                <input :value="todayIso" type="date" disabled
                                    title="Đăng ký khám luôn thuộc ngày hôm nay. Cần hẹn ngày khác thì dùng nút ＋ Lịch hẹn."
                                    class="w-full border border-gray-200 bg-gray-50 text-gray-700 rounded-lg px-3 py-2 pr-8 text-sm cursor-not-allowed" />
                                <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Hôm nay · {{ todayDisplay }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600 mb-1 block">Giờ khám <span class="text-red-500">*</span></label>
                            <input v-model="form.scheduled_time" type="time" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            <p v-if="errors.scheduled_time" class="text-red-500 text-xs mt-1">{{ errors.scheduled_time }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-600 mb-1 block">Bác sĩ</label>
                            <select v-model="form.doctor_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option :value="null">— Chưa chọn —</option>
                                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600 mb-1 block">Ghế nha</label>
                            <select v-model="form.dental_chair_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option :value="null">— Chưa chọn —</option>
                                <option v-for="c in chairs" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-600 mb-1 block">Trạng thái <span class="text-red-500">*</span></label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="s in statuses" :key="s.value" type="button"
                                @click="form.status = s.value"
                                :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors',
                                    form.status === s.value ? statusActiveClass(s.color) : 'border-gray-200 text-gray-500 hover:border-gray-300']">
                                {{ s.label }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-600 mb-1 block">Nội dung khám / Ghi chú</label>
                        <textarea v-model="form.notes" rows="3" placeholder="Nhập nội dung khám, triệu chứng, ghi chú..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none"></textarea>
                    </div>

                    <p v-if="errors.general" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{{ errors.general }}</p>
                </form>

                <div class="flex-shrink-0 px-5 py-4 border-t border-gray-100 flex items-center justify-between gap-2 bg-gray-50">
                    <a :href="route('patients.register-appointment', patient.id)"
                        class="text-xs text-gray-500 hover:text-indigo-600 underline">Mở trang đầy đủ (lịch sử, in phiếu)</a>
                    <div class="flex gap-2">
                        <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-100">Hủy</button>
                        <button type="button" @click="submit" :disabled="saving || !form.status"
                            class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 flex items-center gap-1.5">
                            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Đăng ký khám
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    patient:  { type: Object, required: true },
    doctors:  { type: Array, default: () => [] },
    chairs:   { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});
const emit = defineEmits(['close', 'registered']);

const now = new Date();
const todayDisplay = now.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' });
const todayIso = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

const form = ref({
    scheduled_time: now.toTimeString().slice(0, 5),
    doctor_id: null,
    dental_chair_id: null,
    status: 'pending',
    notes: '',
});
const saving = ref(false);
const errors = ref({});

const STATUS_ACTIVE = {
    yellow: 'bg-yellow-500 text-white border-yellow-500',
    blue:   'bg-blue-600 text-white border-blue-600',
    indigo: 'bg-indigo-600 text-white border-indigo-600',
    teal:   'bg-teal-600 text-white border-teal-600',
    purple: 'bg-purple-600 text-white border-purple-600',
    green:  'bg-green-600 text-white border-green-600',
    red:    'bg-red-600 text-white border-red-600',
    gray:   'bg-gray-600 text-white border-gray-600',
};
function statusActiveClass(color) { return STATUS_ACTIVE[color] ?? STATUS_ACTIVE.gray; }

async function submit() {
    if (saving.value || !form.value.status) return;
    saving.value = true;
    errors.value = {};
    try {
        const res = await fetch(route('patients.quick-register', props.patient.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify(form.value),
        });
        const json = await res.json();
        if (res.status === 422) { errors.value = mapValidationErrors(json.errors); return; }
        if (!res.ok) { errors.value = { general: json.message ?? 'Không đăng ký khám được.' }; return; }
        emit('registered', json);
    } catch {
        errors.value = { general: 'Lỗi kết nối, vui lòng thử lại.' };
    } finally {
        saving.value = false;
    }
}

function mapValidationErrors(bag) {
    return Object.fromEntries(Object.entries(bag ?? {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
}
</script>
