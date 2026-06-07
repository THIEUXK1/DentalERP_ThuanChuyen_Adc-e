<template>
    <AppLayout :title="branch ? 'Sửa chi nhánh' : 'Thêm chi nhánh'">
        <div class="max-w-2xl mx-auto bg-white rounded-xl border border-gray-200 p-6 space-y-5">
            <h2 class="text-lg font-semibold text-gray-800">
                {{ branch ? 'Cập nhật chi nhánh' : 'Thêm chi nhánh mới' }}
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <FormInput label="Tên chi nhánh" :error="form.errors.name" required>
                    <input v-model="form.name" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <FormInput label="Địa chỉ" :error="form.errors.address">
                    <textarea v-model="form.address" rows="2" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <FormInput label="Điện thoại" :error="form.errors.phone">
                    <input v-model="form.phone" type="tel" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <FormInput label="Quản lý chi nhánh" :error="form.errors.manager_id">
                    <select v-model="form.manager_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option :value="null">-- Chưa chọn --</option>
                        <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </FormInput>
                <FormInput label="Trạng thái">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <span class="text-sm text-gray-700">Hoạt động</span>
                    </label>
                </FormInput>
                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('branches.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</Link>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        {{ branch ? 'Cập nhật' : 'Tạo mới' }}
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

const props = defineProps({ branch: Object, managers: Array });

const form = useForm({
    name:       props.branch?.name ?? '',
    address:    props.branch?.address ?? '',
    phone:      props.branch?.phone ?? '',
    manager_id: props.branch?.manager_id ?? null,
    is_active:  props.branch?.is_active ?? true,
});

function submit() {
    if (props.branch) {
        form.put(route('branches.update', props.branch.id));
    } else {
        form.post(route('branches.store'));
    }
}
</script>
