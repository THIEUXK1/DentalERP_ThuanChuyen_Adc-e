<template>
    <AppLayout title="Mẫu tin nhắn">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Mẫu tin nhắn</h1>
                <button @click="openCreate" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                    + Thêm mẫu
                </button>
            </div>

            <!-- Variable hint -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs text-blue-700">
                Biến có thể dùng: <code class="bg-blue-100 px-1 rounded">{patient_name}</code>
                <code class="bg-blue-100 px-1 rounded ml-1">{clinic_name}</code>
                <code class="bg-blue-100 px-1 rounded ml-1">{date}</code>
            </div>

            <!-- Templates table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tên mẫu</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kênh</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nội dung</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="templates.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có mẫu nào</td>
                        </tr>
                        <tr v-for="t in templates" :key="t.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ t.name }}</td>
                            <td class="px-4 py-3">
                                <span :class="t.channel === 'sms' ? 'bg-blue-100 text-blue-700' : 'bg-teal-100 text-teal-700'"
                                    class="text-xs font-medium px-2 py-0.5 rounded">{{ t.channel_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate">{{ t.content }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="t.is_active ? 'text-green-600' : 'text-gray-400'" class="text-xs font-medium">
                                    {{ t.is_active ? 'Hoạt động' : 'Tắt' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="openEdit(t)" class="text-xs text-gray-400 hover:text-gray-600 mr-2">Sửa</button>
                                <button @click="deleteTemplate(t)" class="text-xs text-red-300 hover:text-red-500">Xóa</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="modal.show" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">{{ modal.id ? 'Sửa mẫu' : 'Thêm mẫu mới' }}</h3>
                        <button @click="modal.show = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="saveTemplate" class="p-5 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên mẫu <span class="text-red-500">*</span></label>
                            <input v-model="modal.name" type="text" required class="input-field" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kênh gửi <span class="text-red-500">*</span></label>
                            <select v-model="modal.channel" required class="input-field">
                                <option v-for="c in channels" :key="c.value" :value="c.value">{{ c.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nội dung <span class="text-red-500">*</span></label>
                            <textarea v-model="modal.content" rows="4" required class="input-field font-mono text-xs" />
                        </div>
                        <div class="flex items-center gap-2">
                            <input v-model="modal.is_active" type="checkbox" id="is_active" class="rounded border-gray-300" />
                            <label for="is_active" class="text-sm text-gray-700">Đang hoạt động</label>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="modal.show = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="saving" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                                {{ modal.id ? 'Cập nhật' : 'Tạo mẫu' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ templates: Array, channels: Array });
const saving = ref(false);
const modal  = reactive({ show: false, id: null, name: '', channel: 'sms', content: '', is_active: true });

function openCreate() { Object.assign(modal, { show: true, id: null, name: '', channel: 'sms', content: '', is_active: true }); }
function openEdit(t)  { Object.assign(modal, { show: true, id: t.id, name: t.name, channel: t.channel, content: t.content, is_active: t.is_active }); }

function saveTemplate() {
    saving.value = true;
    const payload = { name: modal.name, channel: modal.channel, content: modal.content, is_active: modal.is_active };
    const url = modal.id ? route('crm.messages.templates.update', modal.id) : route('crm.messages.templates.store');
    const method = modal.id ? 'put' : 'post';
    router[method](url, payload, { onSuccess: () => { modal.show = false; }, onFinish: () => { saving.value = false; } });
}

function deleteTemplate(t) {
    if (confirm(`Xóa mẫu "${t.name}"?`)) router.delete(route('crm.messages.templates.destroy', t.id));
}
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
