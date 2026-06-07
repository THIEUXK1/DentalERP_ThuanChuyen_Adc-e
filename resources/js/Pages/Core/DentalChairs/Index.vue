<template>
    <AppLayout title="Ghế nha">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Quản lý ghế nha</h2>
                <button @click="showCreate = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Thêm ghế
                </button>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Mã</th>
                            <th class="px-4 py-3 text-left font-medium">Tên</th>
                            <th class="px-4 py-3 text-left font-medium">Chi nhánh</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="chairs.length === 0">
                            <td colspan="5" class="text-center py-8 text-gray-400">Chưa có ghế nha</td>
                        </tr>
                        <tr v-for="c in chairs" :key="c.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ c.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ c.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ c.branch }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="c.is_active ? 'green' : 'gray'">
                                    {{ c.is_active ? 'Hoạt động' : 'Vô hiệu' }}
                                </StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="startEdit(c)" class="text-primary-600 text-xs font-medium">Sửa</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create modal -->
        <Modal :show="showCreate" title="Thêm ghế nha" @close="showCreate = false">
            <div class="space-y-4">
                <FormInput label="Chi nhánh" :error="createForm.errors.branch_id" required>
                    <select v-model="createForm.branch_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option value="">-- Chọn --</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </FormInput>
                <FormInput label="Mã ghế" :error="createForm.errors.code" required>
                    <input v-model="createForm.code" type="text" placeholder="GHE-01" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <FormInput label="Tên ghế" :error="createForm.errors.name" required>
                    <input v-model="createForm.name" type="text" placeholder="Ghế số 1" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
            </div>
            <template #footer>
                <button @click="showCreate = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg">Hủy</button>
                <button @click="submitCreate" :disabled="createForm.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">Tạo</button>
            </template>
        </Modal>

        <!-- Edit modal -->
        <Modal :show="!!editTarget" title="Sửa ghế nha" @close="editTarget = null">
            <div v-if="editTarget" class="space-y-4">
                <FormInput label="Tên ghế" :error="editForm.errors.name" required>
                    <input v-model="editForm.name" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>
                <FormInput label="Trạng thái">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="editForm.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <span class="text-sm">Hoạt động</span>
                    </label>
                </FormInput>
            </div>
            <template #footer>
                <button @click="editTarget = null" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg">Hủy</button>
                <button @click="submitEdit" :disabled="editForm.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">Cập nhật</button>
            </template>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Modal from '@/Components/Shared/Modal.vue';
import FormInput from '@/Components/Shared/FormInput.vue';

defineProps({ chairs: Array, branches: Array });

const showCreate = ref(false);
const editTarget = ref(null);
const createForm = useForm({ branch_id: '', code: '', name: '', is_active: true });
const editForm   = useForm({ name: '', is_active: true });

function submitCreate() {
    createForm.post(route('dental-chairs.store'), {
        onSuccess: () => { showCreate.value = false; createForm.reset(); },
    });
}

function startEdit(chair) {
    editTarget.value = chair;
    editForm.name = chair.name;
    editForm.is_active = chair.is_active;
}

function submitEdit() {
    editForm.put(route('dental-chairs.update', editTarget.value.id), {
        onSuccess: () => { editTarget.value = null; },
    });
}
</script>
