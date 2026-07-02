<template>
    <AppLayout :title="`Đăng ký khám — ${patient.full_name}`">
        <div class="space-y-5 max-w-4xl mx-auto">

            <!-- ── Back + Patient header ──────────────────────────────────── -->
            <div class="flex items-center gap-3">
                <Link :href="route('patients.index')"
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </Link>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Đăng ký khám — {{ patient.full_name }}</h2>
                    <p class="text-xs text-gray-400 font-mono">{{ patient.code }} · {{ patient.phone }}</p>
                </div>
            </div>

            <!-- ── Flash messages ─────────────────────────────────────────── -->
            <div v-if="$page.props.flash?.success"
                class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-emerald-700 text-sm">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ $page.props.flash.success }}
            </div>
            <div v-if="errors.conflict || errors.scheduled_time"
                class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm">
                ⚠ {{ errors.conflict || errors.scheduled_time }}
            </div>

            <!-- ── Registration form ──────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Lịch khám mới — {{ todayDisplay }}
                </h3>

                <form @submit.prevent="submit('stay')" class="space-y-4">
                    <!-- Row 1: time + duration -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-600 mb-1 block">Giờ khám *</label>
                            <input v-model="form.scheduled_time" type="time"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                required />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600 mb-1 block">Thời gian (phút)</label>
                            <input v-model.number="form.duration_minutes" type="number" min="5" max="480" step="5"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>
                    </div>

                    <!-- Row 2: doctor + chair -->
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

                    <!-- Row 3: status -->
                    <div>
                        <label class="text-xs font-medium text-gray-600 mb-1 block">
                            Trạng thái <span class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="s in statuses" :key="s.value" type="button"
                                @click="form.status = s.value"
                                :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors',
                                    form.status === s.value
                                        ? statusActiveClass(s.color)
                                        : 'border-gray-200 text-gray-500 hover:border-gray-300']">
                                {{ s.label }}
                            </button>
                        </div>
                        <p v-if="!form.status" class="mt-1 text-xs text-red-500">Vui lòng chọn trạng thái</p>
                    </div>

                    <!-- Row 4: arrived on time checkbox -->
                    <div class="flex items-center gap-2.5">
                        <input id="arrived_on_time" v-model="form.arrived_on_time" type="checkbox"
                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        <label for="arrived_on_time" class="text-sm text-gray-700 cursor-pointer select-none">
                            ✓ Khách đến đúng giờ hẹn
                        </label>
                    </div>

                    <!-- Row 5: notes -->
                    <div>
                        <label class="text-xs font-medium text-gray-600 mb-1 block">Nội dung khám / Ghi chú</label>
                        <textarea v-model="form.notes" rows="3" placeholder="Nhập nội dung khám, triệu chứng, ghi chú..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none"></textarea>
                    </div>

                    <!-- ── Action buttons ──────────────────────────────────── -->
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100 flex-wrap">
                        <button type="button" @click="submit('stay')" :disabled="form.processing || !form.status"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium">
                            💾 Lưu
                        </button>
                        <button type="button" @click="submit('exit')" :disabled="form.processing || !form.status"
                            class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium">
                            ✅ Lưu và thoát
                        </button>
                        <button type="button" @click="printPage"
                            class="px-4 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 font-medium">
                            🖨 In
                        </button>
                        <Link :href="route('patients.index')"
                            class="px-4 py-2 border border-gray-200 text-gray-500 text-sm rounded-lg hover:bg-gray-50">
                            ✕ Thoát
                        </Link>
                    </div>
                </form>
            </div>

            <!-- ── Appointment history ──────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">
                        Lịch sử đăng ký khám
                        <span class="ml-1.5 text-gray-400 font-normal">({{ appointments.length }})</span>
                    </h3>
                </div>

                <div v-if="appointments.length === 0"
                    class="py-10 text-center text-gray-400 text-sm">
                    Chưa có lịch khám nào
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Mã / Ngày giờ</th>
                                <th class="px-4 py-3 text-left font-medium hidden sm:table-cell">Bác sĩ</th>
                                <th class="px-4 py-3 text-left font-medium hidden md:table-cell">Ghế nha</th>
                                <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                                <th class="px-4 py-3 text-left font-medium hidden lg:table-cell">Ghi chú</th>
                                <th class="px-4 py-3 text-right font-medium">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="a in appointments" :key="a.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-mono text-xs text-gray-400">{{ a.code }}</p>
                                    <p class="font-medium text-gray-800 text-xs mt-0.5">{{ a.scheduled_at }}</p>
                                    <p class="text-xs text-gray-400">{{ a.duration_minutes }} phút</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600 text-xs hidden sm:table-cell">{{ a.doctor }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs hidden md:table-cell">{{ a.chair }}</td>
                                <td class="px-4 py-3">
                                    <StatusBadge :color="a.status_color" class="text-xs">{{ a.status_label }}</StatusBadge>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 hidden lg:table-cell max-w-xs truncate">
                                    {{ a.notes || '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center gap-1.5 justify-end">
                                        <button @click="printAppointment(a)"
                                            class="px-2 py-1 text-xs bg-blue-50 text-blue-600 rounded hover:bg-blue-100">
                                            In
                                        </button>
                                        <button @click="confirmDelete(a)"
                                            class="px-2 py-1 text-xs bg-red-50 text-red-600 rounded hover:bg-red-100">
                                            Xóa
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ── Delete confirm modal ─────────────────────────────────────── -->
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="deleteTarget = null">
            <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6 space-y-4">
                <h4 class="font-semibold text-gray-900">Xác nhận xóa</h4>
                <p class="text-sm text-gray-600">
                    Xóa lịch khám <strong>{{ deleteTarget.code }}</strong> — {{ deleteTarget.scheduled_at }}?
                </p>
                <div class="flex gap-2 justify-end">
                    <button @click="deleteTarget = null" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">Hủy</button>
                    <button @click="doDelete"
                        class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Xóa
                    </button>
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

const props = defineProps({
    patient:      Object,
    appointments: Array,
    doctors:      Array,
    chairs:       Array,
    statuses:     Array,
    errors:       Object,
});

// ── Today display ──────────────────────────────────────────────────────────
const now = new Date();
const todayDisplay = now.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' });
const currentTime  = now.toTimeString().slice(0, 5);

// ── Form ───────────────────────────────────────────────────────────────────
const form = useForm({
    scheduled_time:   currentTime,
    duration_minutes: 30,
    doctor_id:        null,
    dental_chair_id:  null,
    notes:            '',
    arrived_on_time:  false,
    status:           'pending',
    redirect_after:   'stay',
});

function submit(redirectAfter) {
    form.redirect_after = redirectAfter;
    form.post(route('patients.quick-register', props.patient.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (redirectAfter === 'stay') {
                form.reset('notes', 'arrived_on_time');
                form.scheduled_time = new Date().toTimeString().slice(0, 5);
            }
        },
    });
}

// ── Status button colors ───────────────────────────────────────────────────
const STATUS_ACTIVE = {
    blue:   'bg-blue-600 text-white border-blue-600',
    indigo: 'bg-indigo-600 text-white border-indigo-600',
    teal:   'bg-teal-600 text-white border-teal-600',
    purple: 'bg-purple-600 text-white border-purple-600',
    green:  'bg-green-600 text-white border-green-600',
    red:    'bg-red-600 text-white border-red-600',
    gray:   'bg-gray-600 text-white border-gray-600',
};
function statusActiveClass(color) {
    return STATUS_ACTIVE[color] ?? STATUS_ACTIVE.gray;
}

// ── Print ──────────────────────────────────────────────────────────────────
function printPage() {
    window.print();
}

function printAppointment(a) {
    const w = window.open('', '_blank', 'width=600,height=400');
    w.document.write(`
        <html><head><title>Lịch khám ${a.code}</title>
        <style>body{font-family:Arial;padding:20px;font-size:13px}
        h2{font-size:16px}table{width:100%;border-collapse:collapse}
        td{padding:6px 4px;border-bottom:1px solid #eee}
        .label{color:#888;width:140px}</style></head>
        <body>
        <h2>PHIẾU ĐĂNG KÝ KHÁM — ${a.code}</h2>
        <table>
            <tr><td class="label">Bệnh nhân</td><td>${props.patient.full_name} (${props.patient.code})</td></tr>
            <tr><td class="label">SĐT</td><td>${props.patient.phone || '—'}</td></tr>
            <tr><td class="label">Ngày giờ khám</td><td>${a.scheduled_at}</td></tr>
            <tr><td class="label">Thời gian</td><td>${a.duration_minutes} phút</td></tr>
            <tr><td class="label">Bác sĩ</td><td>${a.doctor}</td></tr>
            <tr><td class="label">Ghế nha</td><td>${a.chair}</td></tr>
            <tr><td class="label">Trạng thái</td><td>${a.status_label}</td></tr>
            <tr><td class="label">Ghi chú</td><td>${a.notes || '—'}</td></tr>
        </table>
        <script>window.onload=()=>window.print()<\/script>
        </body></html>
    `);
    w.document.close();
}

// ── Delete ─────────────────────────────────────────────────────────────────
const deleteTarget = ref(null);

function confirmDelete(a) {
    deleteTarget.value = a;
}

function doDelete() {
    router.delete(route('schedule.registrations.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null; },
    });
}
</script>
