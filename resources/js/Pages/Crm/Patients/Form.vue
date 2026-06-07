<template>
    <AppLayout :title="patient ? 'Sửa khách hàng' : 'Thêm khách hàng'">
        <div class="max-w-3xl mx-auto bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">
                {{ patient ? 'Cập nhật hồ sơ khách hàng' : 'Tạo hồ sơ khách hàng mới' }}
            </h2>
            <form @submit.prevent="submit" class="space-y-5">
                <!-- Basic info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormInput label="Họ tên" :error="form.errors.full_name" required>
                        <input v-model="form.full_name" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Số điện thoại" :error="form.errors.phone" required>
                        <input v-model="form.phone" type="tel" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Email" :error="form.errors.email">
                        <input v-model="form.email" type="email" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Ngày sinh" :error="form.errors.dob">
                        <input v-model="form.dob" type="date" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Giới tính" :error="form.errors.gender">
                        <select v-model="form.gender" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn --</option>
                            <option value="male">Nam</option>
                            <option value="female">Nữ</option>
                            <option value="other">Khác</option>
                        </select>
                    </FormInput>
                    <FormInput label="Nguồn khách" :error="form.errors.source">
                        <select v-model="form.source" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn --</option>
                            <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </FormInput>
                    <FormInput label="Chi nhánh" :error="form.errors.branch_id">
                        <select v-model="form.branch_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option :value="null">-- Chọn --</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </FormInput>
                    <FormInput label="Người liên hệ khẩn cấp" :error="form.errors.emergency_contact">
                        <input v-model="form.emergency_contact" type="text" placeholder="Tên — SĐT" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                </div>

                <FormInput label="Địa chỉ" :error="form.errors.address">
                    <input v-model="form.address" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>

                <!-- Medical -->
                <div class="border-t pt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Thông tin y tế</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <FormInput label="Dị ứng" :error="form.errors.allergies">
                            <textarea v-model="form.allergies" rows="2" placeholder="Liệt kê các dị ứng..." class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                        </FormInput>
                        <FormInput label="Tiền sử bệnh" :error="form.errors.medical_history">
                            <textarea v-model="form.medical_history" rows="2" placeholder="Tiền sử bệnh lý..." class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                        </FormInput>
                    </div>
                </div>

                <FormInput label="Ghi chú" :error="form.errors.notes">
                    <textarea v-model="form.notes" rows="2" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>

                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('patients.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</Link>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        {{ patient ? 'Cập nhật' : 'Tạo hồ sơ' }}
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

const props = defineProps({ patient: Object, branches: Array, sources: Array });

const form = useForm({
    full_name:         props.patient?.full_name ?? '',
    phone:             props.patient?.phone ?? '',
    email:             props.patient?.email ?? '',
    dob:               props.patient?.dob ?? '',
    gender:            props.patient?.gender ?? '',
    address:           props.patient?.address ?? '',
    source:            props.patient?.source ?? '',
    allergies:         props.patient?.allergies ?? '',
    medical_history:   props.patient?.medical_history ?? '',
    emergency_contact: props.patient?.emergency_contact ?? '',
    branch_id:         props.patient?.branch_id ?? null,
    notes:             props.patient?.notes ?? '',
    is_active:         props.patient?.is_active ?? true,
});

function submit() {
    if (props.patient) {
        form.put(route('patients.update', props.patient.id));
    } else {
        form.post(route('patients.store'));
    }
}
</script>
