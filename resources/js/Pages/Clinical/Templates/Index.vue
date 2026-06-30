<template>
    <AppLayout title="Thư viện mẫu lâm sàng">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Thư viện mẫu lâm sàng</h1>
                <button @click="openCreate" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                    + Thêm mẫu
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" type="text" placeholder="Tìm theo tiêu đề, nội dung..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none w-64" />
                <div class="flex gap-1">
                    <button v-for="t in types" :key="t.value"
                        @click="toggleType(t.value)"
                        :class="['px-3 py-2 text-xs rounded-lg border transition-all',
                            filterType === t.value
                                ? `bg-${t.color}-100 border-${t.color}-300 text-${t.color}-700 font-medium`
                                : 'border-gray-200 text-gray-500 hover:bg-gray-50']">
                        {{ t.label }}
                    </button>
                    <button v-if="filterType" @click="filterType = ''; applyFilters()"
                        class="px-2 py-2 text-xs text-gray-400 hover:text-gray-600">✕</button>
                </div>
            </div>

            <!-- Template grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div v-if="templates.data.length === 0" class="col-span-3 bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400 text-sm">
                    Chưa có mẫu nào
                </div>
                <div v-for="t in templates.data" :key="t.id"
                    class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col gap-2 hover:border-gray-300">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <span :class="`inline-block text-xs font-medium px-2 py-0.5 rounded bg-${t.type_color}-100 text-${t.type_color}-700 mb-1`">
                                {{ t.type_label }}
                            </span>
                            <p class="font-semibold text-gray-800 text-sm truncate">{{ t.title }}</p>
                            <p v-if="t.service" class="text-xs text-gray-400 mt-0.5">{{ t.service }}</p>
                        </div>
                        <div class="flex gap-1 flex-shrink-0">
                            <button @click="openEdit(t)" class="text-xs text-gray-400 hover:text-gray-600 p-1">Sửa</button>
                            <button @click="deleteTemplate(t)" class="text-xs text-red-300 hover:text-red-500 p-1">✕</button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600 line-clamp-3 whitespace-pre-line flex-1">{{ t.content }}</p>
                </div>
            </div>

            <Pagination :links="templates.links" />
        </div>

        <!-- Create / Edit Modal -->
        <Teleport to="body">
            <div v-if="modal.show" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">{{ modal.id ? 'Sửa mẫu' : 'Thêm mẫu mới' }}</h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
                    </div>
                    <form @submit.prevent="saveTemplate" class="p-5 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loại mẫu <span class="text-red-500">*</span></label>
                            <select v-model="modal.type" required class="input-field">
                                <option value="">-- Chọn --</option>
                                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span class="text-red-500">*</span></label>
                            <input v-model="modal.title" type="text" required class="input-field" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nội dung mẫu <span class="text-red-500">*</span></label>
                            <textarea v-model="modal.content" rows="5" required class="input-field font-mono text-xs" />
                        </div>
                        <div class="flex items-center gap-2">
                            <input v-model="modal.is_global" type="checkbox" id="is_global" class="rounded border-gray-300" />
                            <label for="is_global" class="text-sm text-gray-700">Dùng chung toàn hệ thống</label>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="closeModal" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
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
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Pagination from '@/Components/Shared/Pagination.vue';

const props = defineProps({ templates: Object, filters: Object, types: Array });

const search     = ref(props.filters.search ?? '');
const filterType = ref(props.filters.type ?? '');
const saving     = ref(false);

const modal = reactive({ show: false, id: null, type: '', title: '', content: '', is_global: true });

function applyFilters() {
    router.get(route('clinical.templates.index'), { search: search.value, type: filterType.value }, { preserveState: true });
}

function toggleType(val) {
    filterType.value = filterType.value === val ? '' : val;
    applyFilters();
}

function openCreate() {
    Object.assign(modal, { show: true, id: null, type: '', title: '', content: '', is_global: true });
}

function openEdit(t) {
    Object.assign(modal, { show: true, id: t.id, type: t.type, title: t.title, content: t.content, is_global: t.is_global });
}

function closeModal() {
    modal.show = false;
}

function saveTemplate() {
    saving.value = true;
    const payload = { type: modal.type, title: modal.title, content: modal.content, is_global: modal.is_global };

    if (modal.id) {
        router.put(route('clinical.templates.update', modal.id), payload, {
            onSuccess: () => { modal.show = false; },
            onFinish: () => { saving.value = false; },
        });
    } else {
        router.post(route('clinical.templates.store'), payload, {
            onSuccess: () => { modal.show = false; },
            onFinish: () => { saving.value = false; },
        });
    }
}

function deleteTemplate(t) {
    if (confirm(`Xóa mẫu "${t.title}"?`)) {
        router.delete(route('clinical.templates.destroy', t.id));
    }
}
</script>

<style scoped>
.input-field {
    @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none;
}
</style>
