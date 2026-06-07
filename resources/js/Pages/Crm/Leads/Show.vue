<template>
    <AppLayout :title="`Lead: ${lead.name}`">
        <div class="max-w-4xl space-y-4">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('crm.leads.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Lead</Link>
                        <span class="font-mono text-xs text-gray-500">{{ lead.code }}</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mt-1">{{ lead.name }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <StatusBadge :color="lead.status_color">{{ lead.status_label }}</StatusBadge>
                        <span class="text-sm text-gray-500">{{ lead.phone }}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link v-if="can('leads.manage')" :href="route('crm.leads.edit', lead.id)"
                        class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Sửa</Link>
                    <button v-if="can('leads.manage') && !lead.converted" @click="showConvert = true"
                        class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">
                        → Chuyển thành BN
                    </button>
                    <Link v-if="lead.converted && lead.patient_id" :href="route('patients.show', lead.patient_id)"
                        class="px-3 py-1.5 text-sm bg-green-50 text-green-700 border border-green-200 rounded-lg">
                        Xem khách hàng
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Left: Info + Transitions -->
                <div class="space-y-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-2">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase">Thông tin</h3>
                        <p class="text-sm"><span class="text-gray-500">Nguồn:</span> <span class="font-medium">{{ lead.source_label ?? '—' }}</span></p>
                        <p class="text-sm"><span class="text-gray-500">Phụ trách:</span> <span class="font-medium">{{ lead.assignee_name ?? '—' }}</span></p>
                        <p class="text-sm"><span class="text-gray-500">Chi nhánh:</span> <span class="font-medium">{{ lead.branch ?? '—' }}</span></p>
                        <p class="text-sm"><span class="text-gray-500">Dịch vụ quan tâm:</span> <span class="font-medium">{{ lead.interest ?? '—' }}</span></p>
                    </div>

                    <!-- Status transitions -->
                    <div v-if="can('leads.manage') && transitions.length > 0" class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase mb-3">Chuyển trạng thái</h3>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="t in transitions" :key="t.value"
                                @click="doTransition(t.value)"
                                class="px-3 py-1.5 text-xs bg-white border border-gray-300 rounded-lg hover:bg-primary-50 hover:border-primary-300 hover:text-primary-700 transition-colors">
                                → {{ t.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Activities + Tasks -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Log activity -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-700">Lịch sử liên hệ</h3>
                            <button @click="showActivity = !showActivity" class="text-xs text-primary-600 hover:text-primary-800">+ Ghi</button>
                        </div>

                        <div v-if="showActivity" class="mb-4 p-3 bg-gray-50 rounded-lg space-y-3">
                            <div class="flex gap-3">
                                <select v-model="actForm.type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-32">
                                    <option v-for="c in contactTypes" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                                <textarea v-model="actForm.content" rows="2" placeholder="Nội dung..."
                                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                            </div>
                            <div class="flex justify-end gap-2">
                                <button @click="showActivity = false" class="px-3 py-1 text-xs text-gray-600 border border-gray-300 rounded-lg">Hủy</button>
                                <button @click="submitActivity" :disabled="actForm.processing"
                                    class="px-3 py-1 text-xs text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">Lưu</button>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            <div v-if="activities.length === 0" class="text-sm text-gray-400 py-2">Chưa có hoạt động</div>
                            <div v-for="a in activities" :key="a.id" class="flex gap-2 text-sm">
                                <StatusBadge :color="a.type_color" class="flex-shrink-0 mt-0.5">{{ a.type_label }}</StatusBadge>
                                <div>
                                    <p class="text-gray-700">{{ a.content }}</p>
                                    <p class="text-xs text-gray-400">{{ a.creator }} · {{ a.created_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Follow-up Tasks</h3>
                        <div v-if="tasks.length === 0" class="text-sm text-gray-400">Không có task</div>
                        <div v-for="t in tasks" :key="t.id" class="flex items-center gap-2 text-sm py-1.5 border-b last:border-0">
                            <StatusBadge :color="t.status_color">{{ t.status_label }}</StatusBadge>
                            <span class="text-gray-700 flex-1">{{ t.note }}</span>
                            <span class="text-xs text-gray-400">{{ t.due_date }}</span>
                            <button v-if="t.status === 'pending'" @click="completeTask(t.id)"
                                class="text-xs text-green-600 hover:text-green-800">Xong</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Convert modal -->
        <Modal :show="showConvert" title="Chuyển lead thành khách hàng" @close="showConvert = false">
            <p class="text-sm text-gray-600 mb-4">Lead <strong>{{ lead.name }}</strong> sẽ được tạo hồ sơ khách hàng mới.</p>
            <FormInput label="Tên khách hàng" :error="convertForm.errors.full_name" required>
                <input v-model="convertForm.full_name" type="text" :placeholder="lead.name"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
            </FormInput>
            <template #footer>
                <button @click="showConvert = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg">Hủy</button>
                <button @click="doConvert" :disabled="convertForm.processing"
                    class="px-4 py-2 text-sm text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50">
                    Chuyển
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Modal from '@/Components/Shared/Modal.vue';
import FormInput from '@/Components/Shared/FormInput.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ lead: Object, activities: Array, tasks: Array, transitions: Array, contactTypes: Array, assignees: Array });

const showActivity = ref(false);
const showConvert  = ref(false);

const actForm     = useForm({ type: 'call', content: '', lead_id: props.lead.id });
const convertForm = useForm({ full_name: props.lead.name });

function submitActivity() {
    actForm.post(route('crm.activities.store'), {
        onSuccess: () => { showActivity.value = false; actForm.reset('content'); },
    });
}

function doTransition(status) {
    router.post(route('crm.leads.transition', props.lead.id), { status });
}

function doConvert() {
    convertForm.post(route('crm.leads.convert', props.lead.id), {
        onSuccess: () => { showConvert.value = false; },
    });
}

function completeTask(id) {
    router.post(route('crm.tasks.complete', id));
}
</script>
