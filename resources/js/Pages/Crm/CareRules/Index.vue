<template>
    <AppLayout title="Quy tắc CSKH">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Quy tắc chăm sóc khách hàng</h1>
                <button @click="openCreate" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                    + Thêm quy tắc
                </button>
            </div>

            <!-- Info box -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-700">
                Quy tắc được đánh giá mỗi ngày lúc chạy lệnh <code class="bg-amber-100 px-1 rounded">php artisan dental:care-reminders</code>.
                Tin nhắn chỉ gửi khi số ngày kể từ sự kiện = delay_days.
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tên quy tắc</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sự kiện</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dịch vụ</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Sau (ngày)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mẫu tin</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Kênh</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Bật</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="rules.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có quy tắc nào</td>
                        </tr>
                        <tr v-for="r in rules" :key="r.id" :class="['hover:bg-gray-50', !r.is_active ? 'opacity-50' : '']">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ r.name }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ r.trigger_event_label }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ r.trigger_service ?? 'Tất cả' }}</td>
                            <td class="px-4 py-3 text-center font-mono text-gray-700">{{ r.delay_days }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ r.template }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="r.channel === 'SMS' ? 'bg-blue-100 text-blue-700' : 'bg-teal-100 text-teal-700'"
                                    class="text-xs font-medium px-2 py-0.5 rounded">{{ r.channel }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :class="r.is_active ? 'text-green-600' : 'text-gray-300'" class="text-lg">●</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="openEdit(r)" class="text-xs text-gray-400 hover:text-gray-600 mr-2">Sửa</button>
                                <button @click="deleteRule(r)" class="text-xs text-red-300 hover:text-red-500">Xóa</button>
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
                        <h3 class="font-semibold text-gray-800">{{ modal.id ? 'Sửa quy tắc' : 'Thêm quy tắc CSKH' }}</h3>
                        <button @click="modal.show = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="saveRule" class="p-5 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên quy tắc <span class="text-red-500">*</span></label>
                            <input v-model="modal.name" type="text" required class="input-field" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sự kiện kích hoạt <span class="text-red-500">*</span></label>
                                <select v-model="modal.trigger_event" required class="input-field">
                                    <option v-for="e in events" :key="e.value" :value="e.value">{{ e.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sau (ngày) <span class="text-red-500">*</span></label>
                                <input v-model.number="modal.delay_days" type="number" min="0" max="3650" required class="input-field" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dịch vụ (để trống = áp dụng tất cả)</label>
                            <select v-model="modal.trigger_service_id" class="input-field">
                                <option :value="null">Tất cả dịch vụ</option>
                                <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mẫu tin nhắn <span class="text-red-500">*</span></label>
                            <select v-model="modal.message_template_id" required class="input-field">
                                <option :value="null">-- Chọn mẫu --</option>
                                <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }} ({{ t.channel }})</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input v-model="modal.is_active" type="checkbox" id="is_active" class="rounded border-gray-300" />
                            <label for="is_active" class="text-sm text-gray-700">Đang bật</label>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="modal.show = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="saving" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                                {{ modal.id ? 'Cập nhật' : 'Tạo quy tắc' }}
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

const props  = defineProps({ rules: Array, templates: Array, services: Array, events: Array });
const saving = ref(false);
const modal  = reactive({ show: false, id: null, name: '', trigger_event: 'appointment_completed', trigger_service_id: null, delay_days: 7, message_template_id: null, is_active: true });

function openCreate() { Object.assign(modal, { show: true, id: null, name: '', trigger_event: 'appointment_completed', trigger_service_id: null, delay_days: 7, message_template_id: null, is_active: true }); }
function openEdit(r)  { Object.assign(modal, { show: true, id: r.id, name: r.name, trigger_event: r.trigger_event, trigger_service_id: r.trigger_service_id ?? null, delay_days: r.delay_days, message_template_id: r.message_template_id, is_active: r.is_active }); }

function saveRule() {
    saving.value = true;
    const payload = { name: modal.name, trigger_event: modal.trigger_event, trigger_service_id: modal.trigger_service_id, delay_days: modal.delay_days, message_template_id: modal.message_template_id, is_active: modal.is_active };
    const url    = modal.id ? route('crm.care-rules.update', modal.id) : route('crm.care-rules.store');
    const method = modal.id ? 'put' : 'post';
    router[method](url, payload, { onSuccess: () => { modal.show = false; }, onFinish: () => { saving.value = false; } });
}

function deleteRule(r) {
    if (confirm(`Xóa quy tắc "${r.name}"?`)) router.delete(route('crm.care-rules.destroy', r.id));
}
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
