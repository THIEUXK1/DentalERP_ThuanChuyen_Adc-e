<template>
    <AppLayout :title="employee ? 'Sửa nhân viên' : 'Thêm nhân viên'">
        <div class="max-w-2xl mx-auto bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">
                {{ employee ? 'Cập nhật nhân viên' : 'Thêm nhân viên mới' }}
            </h2>
            <form @submit.prevent="submit" class="space-y-4">
                <FormInput label="Chi nhánh" :error="form.errors.branch_id" required>
                    <select v-model="form.branch_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option value="">-- Chọn chi nhánh --</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </FormInput>
                <FormInput label="Họ tên" :error="form.errors.full_name" required>
                    <input v-model="form.full_name" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <FormInput label="Điện thoại" :error="form.errors.phone">
                    <input v-model="form.phone" type="tel" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <FormInput label="Chức danh" :error="form.errors.role_type" required>
                    <select v-model="form.role_type" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option value="">-- Chọn chức danh --</option>
                        <option v-for="r in roleTypes" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </select>
                </FormInput>
                <template v-if="form.role_type === 'doctor'">
                    <FormInput label="Chuyên khoa" :error="form.errors.specialization">
                        <input v-model="form.specialization" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Số chứng chỉ hành nghề" :error="form.errors.license_number">
                        <input v-model="form.license_number" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                </template>
                <FormInput label="Tài khoản đăng nhập" :error="form.errors.user_id">
                    <select v-model="form.user_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option :value="null">-- Chưa gắn tài khoản --</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                    </select>
                </FormInput>
                <FormInput label="Trạng thái">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <span class="text-sm text-gray-700">Hoạt động</span>
                    </label>
                </FormInput>
                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('employees.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</Link>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        {{ employee ? 'Cập nhật' : 'Tạo mới' }}
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

const props = defineProps({ employee: Object, branches: Array, users: Array, roleTypes: Array });

const form = useForm({
    branch_id:      props.employee?.branch_id ?? '',
    full_name:      props.employee?.full_name ?? '',
    phone:          props.employee?.phone ?? '',
    role_type:      props.employee?.role_type ?? '',
    specialization: props.employee?.specialization ?? '',
    license_number: props.employee?.license_number ?? '',
    user_id:        props.employee?.user_id ?? null,
    is_active:      props.employee?.is_active ?? true,
});

function submit() {
    if (props.employee) {
        form.put(route('employees.update', props.employee.id));
    } else {
        form.post(route('employees.store'));
    }
}
</script>
