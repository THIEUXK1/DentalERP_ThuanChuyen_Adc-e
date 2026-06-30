<template>
    <AppLayout title="Danh mục bệnh/vấn đề răng miệng">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Danh mục bệnh/vấn đề răng miệng</h1>
                <button v-if="can('dental.manage')" @click="openCreate"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium">
                    + Thêm bệnh/vấn đề
                </button>
            </div>

            <!-- Filters -->
            <div class="flex gap-3 flex-wrap">
                <input v-model="filters.search" @keyup.enter="applyFilters" placeholder="Tìm tên, mã..."
                    class="border rounded-lg px-3 py-2 text-sm w-52" />
                <select v-model="filters.group" @change="applyFilters" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="">Tất cả nhóm</option>
                    <option v-for="g in groups" :key="g.value" :value="g.value">{{ g.label }}</option>
                </select>
                <button @click="applyFilters" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">Lọc</button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên bệnh/vấn đề</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhóm</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mô tả</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th v-if="can('dental.manage')" class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in conditions" :key="c.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-mono text-gray-500">{{ c.code }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ c.name }}</td>
                            <td class="px-4 py-3">
                                <span :class="`inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-${c.group_color}-100 text-${c.group_color}-700`">
                                    {{ c.group_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ c.description || '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="c.is_active ? 'text-green-600' : 'text-gray-400'" class="text-xs font-medium">
                                    {{ c.is_active ? 'Hoạt động' : 'Ẩn' }}
                                </span>
                            </td>
                            <td v-if="can('dental.manage')" class="px-4 py-3 text-right space-x-2">
                                <button @click="openEdit(c)" class="text-xs text-blue-600 hover:underline">Sửa</button>
                                <button @click="doDelete(c)" class="text-xs text-red-500 hover:underline">Xóa</button>
                            </td>
                        </tr>
                        <tr v-if="!conditions.length">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">Chưa có dữ liệu</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                <h2 class="text-lg font-bold">{{ form.id ? 'Sửa' : 'Thêm' }} bệnh/vấn đề</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tên *</label>
                        <input v-model="form.name" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Tên bệnh/vấn đề" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nhóm *</label>
                        <select v-model="form.group" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">-- Chọn nhóm --</option>
                            <option v-for="g in groups" :key="g.value" :value="g.value">{{ g.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                        <textarea v-model="form.description" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Thứ tự</label>
                        <input v-model.number="form.sort_order" type="number" min="0" class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div v-if="form.id" class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active" id="is_active" />
                        <label for="is_active" class="text-sm">Hoạt động</label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button @click="showModal = false" class="px-4 py-2 border rounded-lg text-sm">Hủy</button>
                    <button @click="submit" :disabled="inertiaForm.processing"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">
                        {{ form.id ? 'Cập nhật' : 'Thêm' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ conditions: Array, groups: Array, filters: Object });

const filters = ref({ search: props.filters?.search ?? '', group: props.filters?.group ?? '' });
const showModal = ref(false);
const form = ref({});

const inertiaForm = useForm({});

function applyFilters() {
    router.get(route('dental.conditions.index'), filters.value, { preserveState: true });
}

function openCreate() {
    form.value = { name: '', group: '', description: '', sort_order: 0, is_active: true };
    showModal.value = true;
}

function openEdit(c) {
    form.value = { ...c };
    showModal.value = true;
}

function submit() {
    if (form.value.id) {
        router.put(route('dental.conditions.update', form.value.id), form.value, {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        router.post(route('dental.conditions.store'), form.value, {
            onSuccess: () => { showModal.value = false; },
        });
    }
}

function doDelete(c) {
    if (!confirm(`Xóa bệnh/vấn đề "${c.name}"?`)) return;
    router.delete(route('dental.conditions.destroy', c.id));
}
</script>
