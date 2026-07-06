<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex justify-end">
            <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>
            <div class="relative bg-white w-full max-w-lg h-full flex flex-col shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
                    <h3 class="text-base font-semibold text-gray-900">Đặt lịch hẹn mới</h3>
                    <button @click="$emit('close')" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <div v-if="form.errors.conflict" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                        ⚠ {{ form.errors.conflict }}
                    </div>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Chi nhánh <span class="text-red-500">*</span></label>
                            <select v-model="form.branch_id" @change="onBranchChange"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="">-- Chọn chi nhánh --</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <p v-if="form.errors.branch_id" class="text-red-500 text-xs mt-1">{{ form.errors.branch_id }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Bác sĩ</label>
                                <select v-model="form.doctor_id"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option :value="null">-- Chọn bác sĩ --</option>
                                    <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Ghế nha</label>
                                <select v-model="form.dental_chair_id"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option :value="null">-- Chọn ghế --</option>
                                    <option v-for="c in filteredChairs" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Dịch vụ</label>
                            <select v-model="form.service_id" @change="onServiceChange"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option :value="null">-- Chọn dịch vụ --</option>
                                <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Ngày & giờ hẹn <span class="text-red-500">*</span></label>
                                <input type="datetime-local" v-model="form.scheduled_at"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                                <p v-if="form.errors.scheduled_at" class="text-red-500 text-xs mt-1">{{ form.errors.scheduled_at }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Thời lượng</label>
                                <select v-model="form.duration_minutes"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option :value="15">15 phút</option>
                                    <option :value="20">20 phút</option>
                                    <option :value="30">30 phút</option>
                                    <option :value="45">45 phút</option>
                                    <option :value="60">60 phút</option>
                                    <option :value="90">90 phút</option>
                                    <option :value="120">2 giờ</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Ghi chú</label>
                            <textarea v-model="form.notes" rows="3" placeholder="Ghi chú thêm..."
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none" />
                        </div>
                    </form>
                </div>
                <div class="flex-shrink-0 px-5 py-4 border-t border-gray-100 flex justify-end gap-2 bg-gray-50">
                    <button @click="$emit('close')" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-100">Hủy</button>
                    <button @click="submit" :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-1.5">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Đặt lịch hẹn
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';

const props = defineProps({
    patientId:       { type: Number, required: true },
    defaultBranchId: { type: Number, default: null },
    branches:        { type: Array, default: () => [] },
    doctors:         { type: Array, default: () => [] },
    chairs:          { type: Array, default: () => [] },
    services:        { type: Array, default: () => [] },
});
const emit = defineEmits(['close']);

const form = useForm({
    patient_id:        props.patientId,
    branch_id:          props.defaultBranchId ?? '',
    doctor_id:          null,
    dental_chair_id:    null,
    service_id:         null,
    scheduled_at:       dayjs().format('YYYY-MM-DD') + 'T08:00',
    duration_minutes:   30,
    notes:              '',
});

const filteredDoctors = computed(() => props.doctors.filter(d => !form.branch_id || d.branch_id == form.branch_id));
const filteredChairs  = computed(() => props.chairs.filter(c => !form.branch_id || c.branch_id == form.branch_id));

function onBranchChange() { form.doctor_id = null; form.dental_chair_id = null; }
function onServiceChange() {
    const svc = props.services.find(s => s.id === form.service_id);
    if (svc) form.duration_minutes = svc.duration_minutes;
}

function submit() {
    form.post(route('schedule.appointments.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { emit('close'); },
    });
}
</script>
