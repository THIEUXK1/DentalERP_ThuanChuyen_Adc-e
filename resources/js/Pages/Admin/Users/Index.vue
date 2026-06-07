<template>
    <AppLayout title="Quản lý người dùng">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Người dùng hệ thống</h2>
                <Link :href="route('admin.users.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Thêm người dùng
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input
                    v-model="search"
                    @keyup.enter="applyFilters"
                    placeholder="Tên hoặc email..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-primary-500 focus:outline-none"
                />
                <select v-model="role" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả vai trò</option>
                    <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                </select>
                <select v-model="status" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Hoạt động</option>
                    <option value="inactive">Vô hiệu</option>
                </select>
                <button @click="applyFilters"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200">
                    Tìm kiếm
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Tên</th>
                            <th class="px-4 py-3 text-left font-medium">Email</th>
                            <th class="px-4 py-3 text-left font-medium">Vai trò</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-left font-medium">Ngày tạo</th>
                            <th class="px-4 py-3 text-right font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" class="text-center py-8 text-gray-400">Không có người dùng</td>
                        </tr>
                        <tr v-for="u in users.data" :key="u.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ u.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ u.email }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge color="blue">{{ u.roles[0] ?? '—' }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="u.is_active ? 'green' : 'gray'">
                                    {{ u.is_active ? 'Hoạt động' : 'Vô hiệu' }}
                                </StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ u.created_at }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <Link :href="route('admin.users.edit', u.id)"
                                        class="text-primary-600 hover:text-primary-800 text-xs font-medium">
                                        Sửa
                                    </Link>
                                    <button @click="confirmDelete(u)"
                                        class="text-red-500 hover:text-red-700 text-xs font-medium">
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="users.links" :meta="users.meta" @navigate="url => router.get(url)" />
        </div>

        <!-- Confirm delete modal -->
        <Modal :show="!!deleteTarget" title="Xác nhận xóa" @close="deleteTarget = null">
            <p class="text-sm text-gray-600">
                Bạn muốn xóa người dùng <strong>{{ deleteTarget?.name }}</strong>?
            </p>
            <template #footer>
                <button @click="deleteTarget = null"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Hủy
                </button>
                <button @click="doDelete"
                    class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700">
                    Xóa
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import Modal from '@/Components/Shared/Modal.vue';

const props = defineProps({
    users:   Object,
    roles:   Array,
    filters: Object,
});

const search      = ref(props.filters.search ?? '');
const role        = ref(props.filters.role ?? '');
const status      = ref(props.filters.status ?? '');
const deleteTarget = ref(null);

function applyFilters() {
    router.get(route('admin.users.index'), { search: search.value, role: role.value, status: status.value }, { preserveState: true });
}

function confirmDelete(u) { deleteTarget.value = u; }

function doDelete() {
    router.delete(route('admin.users.destroy', deleteTarget.value.id), {
        onSuccess: () => { deleteTarget.value = null; },
    });
}
</script>
