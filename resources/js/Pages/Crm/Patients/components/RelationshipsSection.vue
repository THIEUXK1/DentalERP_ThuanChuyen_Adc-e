<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/Shared/SearchableSelect.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({
    patientId: Number,
    relationships: Array,
    relationshipTypes: Array,
    allPatients: Array,
});
const emit = defineEmits(['need-patients']);

const showForm = ref(false);
const form = useForm({ related_patient_id: null, relationship_type: 'referrer', referral_rate: null, notes: '' });

function openForm() {
    emit('need-patients');
    showForm.value = !showForm.value;
}

function submit() {
    form.post(route('patient-relationships.store', props.patientId), {
        onSuccess: () => { showForm.value = false; form.reset(); },
    });
}

function remove(id) {
    if (!confirm('Xóa mối quan hệ này?')) return;
    useForm({}).delete(route('patient-relationships.destroy', id));
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700">Gia đình / Người giới thiệu</h3>
            <button v-if="can('patients.edit')" @click="openForm"
                class="px-3 py-1 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                + Thêm
            </button>
        </div>

        <!-- Form -->
        <div v-if="showForm" class="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Bệnh nhân *</label>
                    <SearchableSelect
                        v-model="form.related_patient_id"
                        :options="allPatients.map(p => ({ value: p.id, label: p.name + ' (' + p.code + ')' }))"
                        placeholder="Tìm bệnh nhân..."
                    />
                    <p v-if="form.errors.related_patient_id" class="text-red-500 text-xs mt-1">{{ form.errors.related_patient_id }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Mối quan hệ *</label>
                    <select v-model="form.relationship_type" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option v-for="r in relationshipTypes" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </select>
                </div>
            </div>
            <div v-if="form.relationship_type === 'referrer'" class="mb-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Tỷ lệ hoa hồng (%)</label>
                <input v-model="form.referral_rate" type="number" min="0" max="100" step="0.1"
                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
            </div>
            <div class="flex gap-2">
                <button @click="submit" :disabled="form.processing"
                    class="px-3 py-1.5 bg-primary-600 text-white text-sm rounded-lg disabled:opacity-50">Lưu</button>
                <button @click="showForm = false" class="px-3 py-1.5 border border-gray-300 text-sm rounded-lg">Hủy</button>
            </div>
        </div>

        <!-- List -->
        <div v-if="relationships.length === 0" class="text-sm text-gray-400 py-3">Chưa có thông tin</div>
        <div v-else class="space-y-2">
            <div v-for="r in relationships" :key="r.id"
                class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ r.related_patient_name }}
                        <span class="text-gray-400 text-xs ml-1">{{ r.related_patient_code }}</span>
                    </p>
                    <p class="text-xs text-gray-500">{{ r.relationship_label }}
                        <span v-if="r.referral_rate"> · {{ r.referral_rate }}%</span>
                    </p>
                </div>
                <button v-if="can('patients.edit')" @click="remove(r.id)" class="text-red-400 hover:text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
