<template>
    <AppLayout title="Khôi phục dữ liệu">
        <div class="space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Khôi phục dữ liệu</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Danh sách các thay đổi gần đây — có thể khôi phục về trước khi sửa/xóa/tạo nhầm.
                    Chỉ thao tác <strong>gần nhất</strong> trên mỗi bản ghi mới khôi phục được, để tránh ghi đè các thay đổi sau đó.
                </p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên người thực hiện..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="subjectType" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả loại</option>
                    <option v-for="t in subjectTypes" :key="t" :value="t">{{ t }}</option>
                </select>
                <input type="date" v-model="date" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Thời gian</th>
                            <th class="px-4 py-3 text-left font-medium">Người thực hiện</th>
                            <th class="px-4 py-3 text-left font-medium">Hành động</th>
                            <th class="px-4 py-3 text-left font-medium">Đối tượng</th>
                            <th class="px-4 py-3 text-left font-medium">Thay đổi</th>
                            <th class="px-4 py-3 text-right font-medium">Khôi phục</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="activities.data.length === 0">
                            <td colspan="6" class="text-center py-8 text-gray-400">Không có dữ liệu</td>
                        </tr>
                        <template v-for="a in activities.data" :key="a.id">
                            <tr class="hover:bg-gray-50 text-xs">
                                <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ a.created_at }}</td>
                                <td class="px-4 py-2.5 font-medium text-gray-700">{{ a.causer }}</td>
                                <td class="px-4 py-2.5">
                                    <StatusBadge :color="eventColor(a.event)">{{ eventLabel(a.event) }}</StatusBadge>
                                </td>
                                <td class="px-4 py-2.5 text-gray-600">{{ a.subject_type }} #{{ a.subject_id }}</td>
                                <td class="px-4 py-2.5">
                                    <button v-if="a.diff.length" @click="toggleExpand(a.id)" class="text-primary-600 hover:underline">
                                        {{ expanded.has(a.id) ? 'Thu gọn' : `${a.diff.length} trường thay đổi` }}
                                    </button>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <span v-if="!a.is_latest" class="text-gray-300 text-xs" title="Đã có thay đổi mới hơn trên bản ghi này">
                                        Đã lỗi thời
                                    </span>
                                    <button v-else-if="a.restore_label" @click="confirmRestore(a)"
                                        class="px-2.5 py-1 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition-colors">
                                        {{ a.restore_label }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="expanded.has(a.id) && a.diff.length" class="bg-gray-50/60">
                                <td colspan="6" class="px-4 py-3">
                                    <table class="w-full text-xs">
                                        <thead class="text-gray-400">
                                            <tr>
                                                <th class="text-left font-medium pb-1 pr-4">Trường</th>
                                                <th class="text-left font-medium pb-1 pr-4">Giá trị cũ</th>
                                                <th class="text-left font-medium pb-1">Giá trị mới</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="d in a.diff" :key="d.field">
                                                <td class="py-1 pr-4 font-mono text-gray-500">{{ d.field }}</td>
                                                <td class="py-1 pr-4 text-red-600">{{ formatVal(d.old) }}</td>
                                                <td class="py-1 text-emerald-600">{{ formatVal(d.new) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <Pagination :links="activities.links" :meta="activities.meta" @navigate="url => router.get(url)" />
        </div>

        <!-- Confirm restore modal -->
        <Teleport to="body">
            <div v-if="pendingRestore" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="pendingRestore = null" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                    <div>
                        <h3 class="font-bold text-gray-800">Xác nhận khôi phục</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ pendingRestore.restore_label }} — {{ pendingRestore.subject_type }} #{{ pendingRestore.subject_id }}</p>
                    </div>
                    <p v-if="pendingRestore.event === 'deleted' && !isSoftDeletableHint(pendingRestore)"
                        class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Bản ghi này đã bị xóa vĩnh viễn — khôi phục sẽ <strong>tạo lại bản ghi mới</strong> từ dữ liệu đã lưu,
                        các liên kết đến bản ghi cũ (nếu có) sẽ không tự động nối lại.
                    </p>

                    <label class="flex items-start gap-2 text-sm text-gray-700 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 cursor-pointer">
                        <input v-model="commitmentChecked" type="checkbox" class="mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500" />
                        <span>
                            Tôi xác nhận muốn thực hiện thao tác này và chịu trách nhiệm về kết quả.
                            Tài khoản <strong>{{ currentUserName }}</strong> sẽ được ghi lại là người xác nhận.
                        </span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="pendingRestore = null" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Hủy
                        </button>
                        <button @click="doRestore" :disabled="restoring || !commitmentChecked"
                            class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                            {{ restoring ? 'Đang khôi phục...' : 'Xác nhận khôi phục' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';

const currentUserName = usePage().props.auth.user.name;

const props = defineProps({ activities: Object, subjectTypes: Array, filters: Object });
const search      = ref(props.filters.search ?? '');
const subjectType = ref(props.filters.subject_type ?? '');
const date        = ref(props.filters.date ?? '');
const expanded    = ref(new Set());

function toggleExpand(id) {
    expanded.value.has(id) ? expanded.value.delete(id) : expanded.value.add(id);
    expanded.value = new Set(expanded.value);
}

function applyFilters() {
    router.get(route('admin.data-restore.index'), { search: search.value, subject_type: subjectType.value, date: date.value }, { preserveState: true });
}

function eventColor(event) {
    return { created: 'green', updated: 'blue', deleted: 'red' }[event] ?? 'gray';
}
function eventLabel(event) {
    return { created: 'Tạo mới', updated: 'Sửa', deleted: 'Xóa' }[event] ?? event;
}
function formatVal(v) {
    if (v === null || v === undefined || v === '') return '—';
    if (typeof v === 'object') return JSON.stringify(v);
    return String(v);
}
function isSoftDeletableHint(a) {
    return a.restore_label?.includes('xóa mềm');
}

const pendingRestore = ref(null);
const restoring = ref(false);
const commitmentChecked = ref(false);

function confirmRestore(a) {
    commitmentChecked.value = false;
    pendingRestore.value = a;
}

function doRestore() {
    if (!pendingRestore.value || restoring.value || !commitmentChecked.value) return;
    restoring.value = true;
    router.post(route('admin.data-restore.restore', pendingRestore.value.id), { confirmed: true }, {
        preserveScroll: true,
        onFinish: () => {
            restoring.value = false;
            pendingRestore.value = null;
            commitmentChecked.value = false;
        },
    });
}
</script>
