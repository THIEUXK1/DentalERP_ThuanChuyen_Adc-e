<template>
    <AppLayout :title="service ? 'Sửa dịch vụ' : 'Thêm dịch vụ'">
        <div class="max-w-2xl mx-auto bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">
                {{ service ? 'Cập nhật dịch vụ' : 'Thêm dịch vụ mới' }}
            </h2>
            <form @submit.prevent="submit" class="space-y-4">
                <FormInput label="Tên dịch vụ" :error="form.errors.name" required>
                    <input v-model="form.name" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <FormInput label="Phân loại" :error="form.errors.category">
                    <input v-model="form.category" type="text" placeholder="Vd: Niềng răng, Implant, Nhổ răng..." class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <div class="grid grid-cols-2 gap-4">
                    <FormInput label="Giá vốn (₫)" :error="form.errors.cost_price" required>
                        <input v-model="form.cost_price" type="number" min="0" step="1000" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Giá bán (₫)" :error="form.errors.selling_price" required>
                        <input v-model="form.selling_price" type="number" min="0" step="1000" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                </div>
                <FormInput label="Thời gian thực hiện (phút)" :error="form.errors.duration_minutes" required>
                    <input v-model="form.duration_minutes" type="number" min="5" max="480" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <FormInput label="Trạng thái">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <span class="text-sm text-gray-700">Hoạt động</span>
                    </label>
                </FormInput>
                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('catalog.services.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</Link>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        {{ service ? 'Cập nhật' : 'Tạo mới' }}
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

const props = defineProps({ service: Object });

const form = useForm({
    name:             props.service?.name ?? '',
    category:         props.service?.category ?? '',
    cost_price:       props.service?.cost_price ?? 0,
    selling_price:    props.service?.selling_price ?? 0,
    duration_minutes: props.service?.duration_minutes ?? 30,
    is_active:        props.service?.is_active ?? true,
});

function submit() {
    if (props.service) {
        form.put(route('catalog.services.update', props.service.id));
    } else {
        form.post(route('catalog.services.store'));
    }
}
</script>
