<template>
    <AppLayout :title="`KH: ${patient.full_name}`">
        <div class="max-w-4xl space-y-4">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('patients.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Danh sách</Link>
                        <span class="font-mono text-xs text-gray-500">{{ patient.code }}</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mt-1">{{ patient.full_name }}</h2>
                    <p class="text-sm text-gray-500">{{ patient.phone }}{{ patient.email ? ` · ${patient.email}` : '' }}</p>
                </div>
                <Link v-if="can('patients.edit')" :href="route('patients.edit', patient.id)"
                    class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Sửa</Link>
            </div>

            <!-- Tab nav -->
            <div class="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit">
                <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                    :class="['px-4 py-1.5 text-sm rounded-md transition-colors',
                        activeTab === tab.key
                            ? 'bg-white shadow text-gray-900 font-medium'
                            : 'text-gray-500 hover:text-gray-700']">
                    {{ tab.label }}
                </button>
            </div>

            <!-- Tab: Thông tin -->
            <template v-if="activeTab === 'info'">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-2">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase">Thông tin cá nhân</h3>
                        <dl class="space-y-1 text-sm">
                            <InfoRow label="Giới tính" :value="genderLabel" />
                            <InfoRow label="Ngày sinh" :value="patient.dob ?? '—'" />
                            <InfoRow label="Địa chỉ" :value="patient.address ?? '—'" />
                            <InfoRow label="Nguồn" :value="patient.source ?? '—'" />
                            <InfoRow label="Liên hệ khẩn cấp" :value="patient.emergency_contact ?? '—'" />
                        </dl>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-2">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase">Thông tin y tế</h3>
                        <dl class="space-y-1 text-sm">
                            <InfoRow label="Dị ứng" :value="patient.allergies ?? '—'" />
                            <InfoRow label="Tiền sử bệnh" :value="patient.medical_history ?? '—'" />
                            <InfoRow label="Ghi chú" :value="patient.notes ?? '—'" />
                        </dl>
                    </div>
                </div>

                <!-- Activity log -->
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">Hoạt động chăm sóc</h3>
                        <button @click="showActivity = !showActivity"
                            class="text-xs text-primary-600 hover:text-primary-800">+ Ghi hoạt động</button>
                    </div>
                    <div v-if="showActivity" class="mb-4 p-3 bg-gray-50 rounded-lg space-y-3">
                        <div class="flex gap-3">
                            <select v-model="actForm.type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-36 focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                <option v-for="c in contactTypes" :key="c.value" :value="c.value">{{ c.label }}</option>
                            </select>
                            <textarea v-model="actForm.content" rows="2" placeholder="Nội dung hoạt động..."
                                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                        </div>
                        <div class="flex justify-end gap-2">
                            <button @click="showActivity = false" class="px-3 py-1 text-xs text-gray-600 border border-gray-300 rounded-lg">Hủy</button>
                            <button @click="submitActivity" :disabled="actForm.processing"
                                class="px-3 py-1 text-xs text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">Ghi lại</button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div v-if="activities.length === 0" class="text-sm text-gray-400 text-center py-4">Chưa có hoạt động</div>
                        <div v-for="a in activities" :key="a.id" class="flex gap-3 text-sm">
                            <StatusBadge :color="a.type_color" class="mt-0.5 flex-shrink-0">{{ a.type_label }}</StatusBadge>
                            <div class="flex-1">
                                <p class="text-gray-700">{{ a.content }}</p>
                                <p class="text-xs text-gray-400">{{ a.creator }} · {{ a.created_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Tab: Sơ đồ răng -->
            <DentalChartTab v-if="activeTab === 'chart'"
                :patient="patient"
                :toothConditions="toothConditions"
                :conditionTypes="conditionTypes"
                :canEdit="can('clinical_notes.create')"
            />

            <!-- Tab: Hồ sơ lâm sàng -->
            <ClinicalNotesTab v-if="activeTab === 'clinical'"
                :patient="patient"
                :clinicalNotes="clinicalNotes"
                :doctors="doctors"
                :canCreate="can('clinical_notes.create')"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DentalChartTab from '@/Components/Clinical/DentalChartTab.vue';
import ClinicalNotesTab from '@/Components/Clinical/ClinicalNotesTab.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({
    patient:         Object,
    activities:      Array,
    clinicalNotes:   Array,
    toothConditions: Array,
    doctors:         Array,
    conditionTypes:  Array,
    contactTypes:    Array,
});

const activeTab   = ref('info');
const showActivity = ref(false);
const actForm     = useForm({ type: 'note', content: '', patient_id: props.patient.id });

const tabs = [
    { key: 'info',     label: 'Thông tin' },
    { key: 'chart',    label: 'Sơ đồ răng' },
    { key: 'clinical', label: 'Hồ sơ lâm sàng' },
];

const genderLabel = computed(() => ({ male: 'Nam', female: 'Nữ', other: 'Khác' }[props.patient.gender] ?? '—'));

function submitActivity() {
    actForm.post(route('crm.activities.store'), {
        onSuccess: () => { showActivity.value = false; actForm.reset('content'); },
    });
}

const InfoRow = defineComponent({
    props: { label: String, value: String },
    template: `<div class="flex gap-2"><span class="text-gray-500 min-w-28 flex-shrink-0">{{ label }}</span><span class="text-gray-700 break-words">{{ value }}</span></div>`,
});
</script>
