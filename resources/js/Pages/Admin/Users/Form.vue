<template>
    <AppLayout :title="user ? 'Sửa người dùng' : 'Thêm người dùng'">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-6">
                    {{ user ? 'Cập nhật người dùng' : 'Tạo người dùng mới' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-5">
                    <FormInput label="Họ tên" :error="form.errors.name" required>
                        <input v-model="form.name" type="text"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>

                    <FormInput label="Email" :error="form.errors.email" required>
                        <input v-model="form.email" type="email"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>

                    <FormInput :label="user ? 'Mật khẩu mới (để trống nếu không đổi)' : 'Mật khẩu'" :error="form.errors.password" :required="!user">
                        <input v-model="form.password" type="password"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>

                    <FormInput v-if="form.password" label="Xác nhận mật khẩu" :error="form.errors.password_confirmation" required>
                        <input v-model="form.password_confirmation" type="password"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>

                    <FormInput label="Vai trò" :error="form.errors.role" required>
                        <select v-model="form.role"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn vai trò --</option>
                            <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                        </select>
                    </FormInput>

                    <FormInput label="Trạng thái" :error="form.errors.is_active">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            <span class="text-sm text-gray-700">Hoạt động</span>
                        </label>
                    </FormInput>

                    <div class="flex justify-end gap-3 pt-2">
                        <Link :href="route('admin.users.index')"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Hủy
                        </Link>
                        <button type="submit" :disabled="form.processing"
                            class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                            {{ user ? 'Cập nhật' : 'Tạo mới' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import FormInput from '@/Components/Shared/FormInput.vue';

const props = defineProps({
    user:  Object,
    roles: Array,
});

const form = useForm({
    name:                 props.user?.name ?? '',
    email:                props.user?.email ?? '',
    password:             '',
    password_confirmation: '',
    role:                 props.user?.role ?? '',
    is_active:            props.user?.is_active ?? true,
});

function submit() {
    if (props.user) {
        form.put(route('admin.users.update', props.user.id));
    } else {
        form.post(route('admin.users.store'));
    }
}
</script>
