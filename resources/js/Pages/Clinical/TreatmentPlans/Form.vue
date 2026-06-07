<template>
    <AppLayout :title="plan ? 'Sửa kế hoạch' : 'Tạo kế hoạch điều trị'">
        <div class="max-w-2xl mx-auto bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">
                {{ plan ? 'Cập nhật kế hoạch điều trị' : 'Tạo kế hoạch điều trị mới' }}
            </h2>
            <form @submit.prevent="submit" class="space-y-4">
                <FormInput label="Khách hàng" :error="form.errors.patient_id" required>
                    <SearchableSelect
                        v-model="form.patient_id"
                        :options="patientOptions"
                        placeholder="-- Tìm khách hàng --"
                    />
                </FormInput>
                <FormInput label="Chi nhánh" :error="form.errors.branch_id" required>
                    <select v-model="form.branch_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option value="">-- Chọn chi nhánh --</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </FormInput>
                <div class="grid grid-cols-2 gap-4">
                    <FormInput label="Bác sĩ" :error="form.errors.doctor_id">
                        <select v-model="form.doctor_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option :value="null">-- Chọn bác sĩ --</option>
                            <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </FormInput>
                    <FormInput label="Tư vấn viên" :error="form.errors.consultant_id">
                        <select v-model="form.consultant_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option :value="null">-- Chọn tư vấn --</option>
                            <option v-for="c in consultants" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </FormInput>
                </div>
                <FormInput label="Ghi chú" :error="form.errors.notes">
                    <textarea v-model="form.notes" rows="2" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('clinical.treatment-plans.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</Link>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        {{ plan ? 'Cập nhật' : 'Tạo kế hoạch' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import FormInput from '@/Components/Shared/FormInput.vue';
import SearchableSelect from '@/Components/Shared/SearchableSelect.vue';

const props = defineProps({ plan: Object, patients: Array, doctors: Array, consultants: Array, branches: Array });

const patientOptions = computed(() => props.patients.map(p => ({ value: p.id, label: `${p.code} — ${p.full_name}`, sublabel: p.phone })));

const form = useForm({
    patient_id:     props.plan?.patient_id ?? '',
    branch_id:      props.plan?.branch_id ?? '',
    doctor_id:      props.plan?.doctor_id ?? null,
    consultant_id:  props.plan?.consultant_id ?? null,
    appointment_id: props.plan?.appointment_id ?? null,
    notes:          props.plan?.notes ?? '',
});

function submit() {
    if (props.plan) {
        form.put(route('clinical.treatment-plans.update', props.plan.id));
    } else {
        form.post(route('clinical.treatment-plans.store'));
    }
}
</script>
