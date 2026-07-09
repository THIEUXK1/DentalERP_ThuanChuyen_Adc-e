<template>
    <AppLayout title="Nhóm dịch vụ">
        <div class="max-w-3xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Nhóm dịch vụ</h1>
                <div class="flex items-center gap-2">
                    <Link :href="route('catalog.service-categories.index')"
                        class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">
                        Loại dịch vụ
                    </Link>
                    <button @click="openCreate" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                        + Thêm nhóm
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tên nhóm</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Số loại dịch vụ</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="groups.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có nhóm dịch vụ nào</td>
                        </tr>
                        <tr v-for="g in groups" :key="g.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ g.name }}</td>
                            <td class="px-4 py-3 text-center font-mono text-gray-600">{{ g.categories_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="g.is_active ? 'text-green-600' : 'text-gray-400'" class="text-xs font-medium">
                                    {{ g.is_active ? 'Hoạt động' : 'Tắt' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="openEdit(g)" class="text-xs text-gray-400 hover:text-gray-600 mr-2">Sửa</button>
                                <button @click="remove(g)" class="text-xs text-red-300 hover:text-red-500">Xóa</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="modal.show" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">{{ modal.id ? 'Sửa nhóm dịch vụ' : 'Thêm nhóm dịch vụ' }}</h3>
                        <button @click="modal.show = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="save" class="p-5 space-y-4">
                        <div>
                            <label class="label">Tên nhóm <span class="text-red-500">*</span></label>
                            <input v-model="modal.name" required class="input-field" />
                        </div>
                        <div v-if="modal.id">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="modal.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                <span class="text-sm text-gray-700">Hoạt động</span>
                            </label>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="modal.show = false" class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="saving" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg disabled:opacity-50">
                                {{ modal.id ? 'Cập nhật' : 'Tạo' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

defineProps({ groups: Array });
const saving = ref(false);
const modal  = reactive({ show: false, id: null, name: '', is_active: true });

function openCreate() { Object.assign(modal, { show: true, id: null, name: '', is_active: true }); }
function openEdit(g)  { Object.assign(modal, { show: true, id: g.id, name: g.name, is_active: g.is_active }); }
function remove(g)    { if (confirm(`Xóa nhóm "${g.name}"?`)) router.delete(route('catalog.service-groups.destroy', g.id)); }

function save() {
    saving.value = true;
    const url    = modal.id ? route('catalog.service-groups.update', modal.id) : route('catalog.service-groups.store');
    const method = modal.id ? 'put' : 'post';
    router[method](url, { name: modal.name, is_active: modal.is_active },
        { onSuccess: () => { modal.show = false; }, onFinish: () => { saving.value = false; } });
}
</script>

<style scoped>
.label       { @apply block text-sm font-medium text-gray-700 mb-1; }
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
