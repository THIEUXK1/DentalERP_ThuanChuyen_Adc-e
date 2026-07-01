<template>
    <AppLayout :title="appointment?.id ? 'Sửa lịch hẹn' : 'Đặt lịch hẹn mới'">
        <div class="max-w-3xl mx-auto bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">
                {{ appointment?.id ? 'Cập nhật lịch hẹn' : 'Đặt lịch hẹn mới' }}
            </h2>

            <div v-if="form.errors.conflict" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                ⚠ {{ form.errors.conflict }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Patient search -->
                    <FormInput label="Khách hàng" :error="form.errors.patient_id" required>
                        <SearchableSelect
                            v-model="form.patient_id"
                            :options="patientOptions"
                            placeholder="-- Tìm khách hàng --"
                        />
                    </FormInput>

                    <FormInput label="Chi nhánh" :error="form.errors.branch_id" required>
                        <select v-model="form.branch_id" @change="onBranchChange" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn chi nhánh --</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </FormInput>

                    <FormInput label="Bác sĩ" :error="form.errors.doctor_id">
                        <select v-model="form.doctor_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option :value="null">-- Chọn bác sĩ --</option>
                            <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </FormInput>

                    <FormInput label="Ghế nha" :error="form.errors.dental_chair_id">
                        <select v-model="form.dental_chair_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option :value="null">-- Chọn ghế --</option>
                            <option v-for="c in filteredChairs" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </FormInput>

                    <FormInput label="Dịch vụ" :error="form.errors.service_id">
                        <select v-model="form.service_id" @change="onServiceChange" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option :value="null">-- Chọn dịch vụ --</option>
                            <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </FormInput>

                    <FormInput label="Thời gian (phút)" :error="form.errors.duration_minutes">
                        <input v-model="form.duration_minutes" type="number" min="5" max="480"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                </div>

                <FormInput label="Ngày & giờ hẹn" :error="form.errors.scheduled_at" required>
                    <input v-model="form.scheduled_at" type="datetime-local"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>

                <FormInput label="Ghi chú" :error="form.errors.notes">
                    <textarea v-model="form.notes" rows="2" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>

                <!-- Status — chỉ hiện khi edit -->
                <div v-if="appointment?.id">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="s in statuses" :key="s.value" type="button"
                            @click="form.status = s.value"
                            :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors',
                                form.status === s.value ? activeClass(s.color) : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50']">
                            {{ s.label }}
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('schedule.appointments.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</Link>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        {{ appointment?.id ? 'Cập nhật' : 'Đặt lịch' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import FormInput from '@/Components/Shared/FormInput.vue';
import SearchableSelect from '@/Components/Shared/SearchableSelect.vue';

const props = defineProps({ appointment: Object, patients: Array, branches: Array, doctors: Array, chairs: Array, services: Array, statuses: Array });

const form = useForm({
    patient_id:       props.appointment?.patient_id ?? '',
    branch_id:        props.appointment?.branch_id ?? '',
    doctor_id:        props.appointment?.doctor_id ?? null,
    dental_chair_id:  props.appointment?.dental_chair_id ?? null,
    service_id:       props.appointment?.service_id ?? null,
    lead_id:          props.appointment?.lead_id ?? null,
    scheduled_at:     props.appointment?.scheduled_at ?? '',
    duration_minutes: props.appointment?.duration_minutes ?? 30,
    notes:            props.appointment?.notes ?? '',
    status:           props.appointment?.status ?? 'booked',
});

const STATUS_ACTIVE = {
    gray:   'bg-gray-600 text-white border-gray-600',
    blue:   'bg-blue-600 text-white border-blue-600',
    indigo: 'bg-indigo-600 text-white border-indigo-600',
    yellow: 'bg-yellow-500 text-white border-yellow-500',
    teal:   'bg-teal-600 text-white border-teal-600',
    orange: 'bg-orange-500 text-white border-orange-500',
    red:    'bg-red-600 text-white border-red-600',
    purple: 'bg-purple-600 text-white border-purple-600',
    green:  'bg-green-600 text-white border-green-600',
};
function activeClass(color) {
    return STATUS_ACTIVE[color] ?? STATUS_ACTIVE.gray;
}

const patientOptions  = computed(() => props.patients.map(p => ({ value: p.id, label: `${p.code} — ${p.full_name}`, sublabel: p.phone })));
const filteredDoctors = computed(() => props.doctors.filter(d => !form.branch_id || d.branch_id == form.branch_id));
const filteredChairs  = computed(() => props.chairs.filter(c => !form.branch_id || c.branch_id == form.branch_id));

function onBranchChange() {
    form.doctor_id = null;
    form.dental_chair_id = null;
}

function onServiceChange() {
    const svc = props.services.find(s => s.id === form.service_id);
    if (svc) form.duration_minutes = svc.duration_minutes;
}

function submit() {
    if (props.appointment?.id) {
        form.put(route('schedule.appointments.update', props.appointment.id));
    } else {
        form.post(route('schedule.appointments.store'));
    }
}
</script>
