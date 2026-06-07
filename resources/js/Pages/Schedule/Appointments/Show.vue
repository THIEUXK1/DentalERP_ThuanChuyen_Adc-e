<template>
    <AppLayout :title="`Lịch hẹn ${appointment.code}`">
        <div class="max-w-2xl space-y-4">
            <div class="flex items-center gap-3">
                <Link :href="route('schedule.appointments.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Lịch hẹn</Link>
                <span class="font-mono text-xs text-gray-500">{{ appointment.code }}</span>
                <StatusBadge :color="appointment.status_color">{{ appointment.status_label }}</StatusBadge>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <InfoRow label="Khách hàng" :value="appointment.patient" />
                    <InfoRow label="SĐT" :value="appointment.patient_phone ?? '—'" />
                    <InfoRow label="Thời gian" :value="`${appointment.scheduled_at} → ${appointment.ends_at}`" />
                    <InfoRow label="Thời lượng" :value="`${appointment.duration_minutes} phút`" />
                    <InfoRow label="Bác sĩ" :value="appointment.doctor" />
                    <InfoRow label="Ghế nha" :value="appointment.chair" />
                    <InfoRow label="Dịch vụ" :value="appointment.service" />
                    <InfoRow label="Chi nhánh" :value="appointment.branch" />
                    <InfoRow v-if="appointment.cancel_reason" label="Lý do hủy" :value="appointment.cancel_reason" />
                    <InfoRow v-if="appointment.notes" label="Ghi chú" :value="appointment.notes" />
                </div>
            </div>

            <!-- Transitions -->
            <div v-if="can('appointments.manage') && transitions.length > 0" class="bg-white rounded-xl border border-gray-200 p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Cập nhật trạng thái</h3>
                <div class="flex flex-wrap gap-2">
                    <button v-for="t in transitions" :key="t.value" @click="doTransition(t.value)"
                        class="px-3 py-1.5 text-xs bg-white border border-gray-300 rounded-lg hover:bg-primary-50 hover:border-primary-300 transition-colors">
                        → {{ t.label }}
                    </button>
                </div>
            </div>

            <!-- Tái khám shortcut -->
            <div v-if="can('appointments.create') && appointment.status === 'completed'"
                class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center justify-between">
                <span class="text-sm text-blue-700">Khách hàng đã hoàn thành — lên lịch tái khám?</span>
                <Link :href="route('schedule.appointments.create', {
                    patient_id: appointment.patient_id,
                    doctor_id:  appointment.doctor_id,
                    branch_id:  appointment.branch_id,
                })" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    + Tạo lịch tái khám
                </Link>
            </div>

            <!-- Cancel modal -->
            <Modal :show="showCancel" title="Hủy lịch hẹn" @close="showCancel = false">
                <FormInput label="Lý do hủy" :error="cancelForm.errors.cancel_reason" required>
                    <textarea v-model="cancelForm.cancel_reason" rows="2" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <template #footer>
                    <button @click="showCancel = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg">Không</button>
                    <button @click="submitCancel" :disabled="cancelForm.processing"
                        class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50">Xác nhận hủy</button>
                </template>
            </Modal>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Modal from '@/Components/Shared/Modal.vue';
import FormInput from '@/Components/Shared/FormInput.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ appointment: Object, transitions: Array });

const showCancel  = ref(false);
const cancelForm  = useForm({ status: 'cancelled', cancel_reason: '' });

function doTransition(status) {
    if (status === 'cancelled') { showCancel.value = true; return; }
    router.post(route('schedule.appointments.transition', props.appointment.id), { status });
}

function submitCancel() {
    cancelForm.post(route('schedule.appointments.transition', props.appointment.id), {
        onSuccess: () => { showCancel.value = false; },
    });
}
</script>

<script>
export default { components: {
    InfoRow: {
        props: { label: String, value: String },
        template: `<div class="flex gap-2 col-span-1"><span class="text-gray-500 min-w-24 flex-shrink-0">{{ label }}</span><span class="text-gray-700">{{ value }}</span></div>`
    }
}};
</script>
