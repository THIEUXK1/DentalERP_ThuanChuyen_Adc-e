<template>
    <AppLayout :title="lead ? 'Sửa lead' : 'Thêm lead mới'">
        <div class="max-w-2xl mx-auto bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">{{ lead ? 'Cập nhật lead' : 'Tạo lead mới' }}</h2>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormInput label="Họ tên" :error="form.errors.name" required>
                        <input v-model="form.name" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Số điện thoại" :error="form.errors.phone" required>
                        <input v-model="form.phone" type="tel" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Email" :error="form.errors.email">
                        <input v-model="form.email" type="email" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Nguồn khách" :error="form.errors.source">
                        <select v-model="form.source" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn --</option>
                            <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </FormInput>
                    <FormInput label="Phụ trách" :error="form.errors.assigned_to">
                        <select v-model="form.assigned_to" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option :value="null">-- Chưa gán --</option>
                            <option v-for="u in assignees" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </FormInput>
                    <FormInput label="Chi nhánh" :error="form.errors.branch_id">
                        <select v-model="form.branch_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option :value="null">-- Chọn --</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </FormInput>
                </div>
                <FormInput label="Dịch vụ quan tâm" :error="form.errors.interest">
                    <input v-model="form.interest" type="text" placeholder="Vd: Niềng răng, Implant..." class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <FormInput label="Ghi chú" :error="form.errors.notes">
                    <textarea v-model="form.notes" rows="3" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('crm.leads.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</Link>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        {{ lead ? 'Cập nhật' : 'Tạo lead' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import FormInput from '@/Components/Shared/FormInput.vue';

const props = defineProps({ lead: Object, sources: Array, assignees: Array, branches: Array });

const form = useForm({
    name:        props.lead?.name ?? '',
    phone:       props.lead?.phone ?? '',
    email:       props.lead?.email ?? '',
    source:      props.lead?.source ?? '',
    assigned_to: props.lead?.assigned_to ?? null,
    branch_id:   props.lead?.branch_id ?? null,
    interest:    props.lead?.interest ?? '',
    notes:       props.lead?.notes ?? '',
});

function submit() {
    if (props.lead) {
        form.put(route('crm.leads.update', props.lead.id));
    } else {
        form.post(route('crm.leads.store'));
    }
}
</script>
