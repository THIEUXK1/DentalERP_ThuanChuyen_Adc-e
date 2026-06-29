<template>
    <AppLayout :title="`Lịch hẹn ${appointment.code}`">
        <div class="space-y-4">

            <!-- Header -->
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <Link :href="route('schedule.appointments.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Lịch hẹn</Link>
                            <span class="text-gray-300">/</span>
                            <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ appointment.code }}</span>
                            <StatusBadge :color="appointment.status_color">{{ appointment.status_label }}</StatusBadge>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">{{ appointment.patient }}</h2>
                        <p class="text-sm text-gray-500 mt-0.5">
                            <span v-if="appointment.patient_phone">📞 {{ appointment.patient_phone }}</span>
                            <span v-if="appointment.doctor" class="ml-3">🦷 {{ appointment.doctor }}</span>
                            <span class="ml-3 text-gray-400">{{ appointment.branch }}</span>
                        </p>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <Link v-if="can('appointments.manage')"
                            :href="route('schedule.appointments.edit', appointment.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600">
                            Sửa
                        </Link>
                    </div>
                </div>

                <!-- Time bar -->
                <div class="mt-3 flex flex-wrap gap-4 text-sm bg-indigo-900 rounded-xl px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="text-indigo-300 text-xs">Ngày giờ</span>
                        <span class="font-bold text-white">{{ appointment.scheduled_at }}</span>
                    </div>
                    <div class="h-4 w-px bg-indigo-700 hidden sm:block self-center"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-indigo-300 text-xs">Kết thúc</span>
                        <span class="font-bold text-indigo-200">{{ appointment.ends_at }}</span>
                    </div>
                    <div class="h-4 w-px bg-indigo-700 hidden sm:block self-center"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-indigo-300 text-xs">Thời lượng</span>
                        <span class="font-bold text-indigo-200">{{ appointment.duration_minutes }} phút</span>
                    </div>
                </div>
            </div>

            <!-- Main grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                <!-- LEFT: Chi tiết lịch hẹn -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Thông tin lịch hẹn</h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-gray-400 mb-0.5">Bệnh nhân</dt>
                                <dd class="text-gray-800 font-medium">{{ appointment.patient }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400 mb-0.5">Số điện thoại</dt>
                                <dd class="text-gray-800">{{ appointment.patient_phone || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400 mb-0.5">Bác sĩ phụ trách</dt>
                                <dd class="text-gray-800">{{ appointment.doctor || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400 mb-0.5">Ghế nha</dt>
                                <dd class="text-gray-800">{{ appointment.chair || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400 mb-0.5">Dịch vụ</dt>
                                <dd class="text-gray-800">{{ appointment.service || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400 mb-0.5">Chi nhánh</dt>
                                <dd class="text-gray-800">{{ appointment.branch || '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2" v-if="appointment.notes">
                                <dt class="text-xs text-gray-400 mb-0.5">Ghi chú</dt>
                                <dd class="text-gray-800 whitespace-pre-wrap">{{ appointment.notes }}</dd>
                            </div>
                            <div class="sm:col-span-2" v-if="appointment.cancel_reason">
                                <dt class="text-xs text-red-400 mb-0.5">Lý do hủy</dt>
                                <dd class="text-red-600">{{ appointment.cancel_reason }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- RIGHT: Trạng thái + actions -->
                <div class="space-y-4">

                    <!-- Chuyển trạng thái -->
                    <div v-if="can('appointments.manage') && transitions.length > 0"
                        class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Cập nhật trạng thái
                        </h3>
                        <div class="space-y-2">
                            <button v-for="t in transitions" :key="t.value" @click="doTransition(t.value)"
                                class="w-full px-3 py-2 text-xs text-left bg-white border border-gray-200 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors font-medium">
                                → {{ t.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Tái khám -->
                    <div v-if="can('appointments.create') && appointment.status === 'completed'"
                        class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <p class="text-sm text-blue-700 mb-3">Khách hàng đã hoàn thành — lên lịch tái khám?</p>
                        <Link :href="route('schedule.appointments.create', {
                            patient_id: appointment.patient_id,
                            doctor_id:  appointment.doctor_id,
                            branch_id:  appointment.branch_id,
                        })" class="block w-full text-center px-3 py-2 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            + Tạo lịch tái khám
                        </Link>
                    </div>

                    <!-- Trạng thái hiện tại -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-xs text-gray-400 mb-2">Trạng thái hiện tại</h3>
                        <StatusBadge :color="appointment.status_color" class="text-sm">
                            {{ appointment.status_label }}
                        </StatusBadge>
                    </div>
                </div>
            </div>

        </div>

        <!-- Cancel modal -->
        <Modal :show="showCancel" title="Hủy lịch hẹn" @close="showCancel = false">
            <FormInput label="Lý do hủy" :error="cancelForm.errors.cancel_reason" required>
                <textarea v-model="cancelForm.cancel_reason" rows="3"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
            </FormInput>
            <template #footer>
                <button @click="showCancel = false"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Không</button>
                <button @click="submitCancel" :disabled="cancelForm.processing"
                    class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50">Xác nhận hủy</button>
            </template>
        </Modal>
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

const showCancel = ref(false);
const cancelForm = useForm({ status: 'cancelled', cancel_reason: '' });

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
