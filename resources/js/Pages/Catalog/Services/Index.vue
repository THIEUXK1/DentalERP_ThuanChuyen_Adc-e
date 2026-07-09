<template>
    <AppLayout title="Dịch vụ nha khoa">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Danh mục dịch vụ</h2>
                <div class="flex items-center gap-2">
                    <Link v-if="can('services.manage')" :href="route('catalog.service-categories.index')"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50">
                        Loại dịch vụ
                    </Link>
                    <button v-if="can('services.manage')" @click="openCreate"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                        + Thêm dịch vụ
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên hoặc mã dịch vụ..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <select v-model="categoryId" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả loại</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Mã</th>
                            <th class="px-4 py-3 text-left font-medium">Tên dịch vụ</th>
                            <th class="px-4 py-3 text-left font-medium">Loại</th>
                            <th class="px-4 py-3 text-right font-medium">Giá bán</th>
                            <th class="px-4 py-3 text-left font-medium">Thời gian</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="services.data.length === 0">
                            <td colspan="7" class="text-center py-8 text-gray-400">Chưa có dịch vụ</td>
                        </tr>
                        <tr v-for="s in services.data" :key="s.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ s.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ s.name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ s.category ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-800">{{ formatVnd(s.selling_price) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ s.duration_minutes }} phút</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="s.is_active ? 'green' : 'gray'">
                                    {{ s.is_active ? 'Hoạt động' : 'Vô hiệu' }}
                                </StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="can('services.manage')" @click="openEdit(s)"
                                    class="text-primary-600 hover:text-primary-800 text-xs font-medium">Sửa</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="services.links" :meta="services.meta" @navigate="url => router.get(url)" />
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="modal.show" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">{{ modal.id ? 'Sửa dịch vụ' : 'Thêm dịch vụ' }}</h3>
                        <button @click="modal.show = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="save" class="p-5 space-y-4">
                        <FormInput label="Tên dịch vụ" :error="form.errors.name" required>
                            <input v-model="form.name" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                        </FormInput>
                        <FormInput label="Nhóm dịch vụ" :error="form.errors.category_id">
                            <select v-model="form.category_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                <option :value="null">— Không chọn —</option>
                                <option v-for="c in categoryOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
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
                            <button type="button" @click="modal.show = false" class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                                {{ modal.id ? 'Cập nhật' : 'Tạo mới' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import FormInput from '@/Components/Shared/FormInput.vue';
import { usePermission } from '@/composables/usePermission';
import { useCurrency } from '@/composables/useCurrency';

const { hasPermission: can } = usePermission();
const { formatVnd } = useCurrency();
const props = defineProps({ services: Object, categories: Array, filters: Object });
const search     = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ?? '');

function applyFilters() {
    router.get(route('catalog.services.index'), { search: search.value, category_id: categoryId.value }, { preserveState: true });
}

const modal = reactive({ show: false, id: null, currentCategory: null });
const form = useForm({
    name: '',
    category_id: null,
    cost_price: 0,
    selling_price: 0,
    duration_minutes: 30,
    is_active: true,
});

// Đảm bảo nhóm dịch vụ hiện tại của dịch vụ vẫn hiển thị trong select dù đã bị tắt hoạt động
const categoryOptions = computed(() => {
    if (modal.currentCategory && !props.categories.some(c => c.id === modal.currentCategory.id)) {
        return [...props.categories, modal.currentCategory];
    }
    return props.categories;
});

function openCreate() {
    modal.id = null;
    modal.currentCategory = null;
    modal.show = true;
    form.reset();
    form.clearErrors();
}

function openEdit(s) {
    modal.id = s.id;
    modal.currentCategory = s.category_id ? { id: s.category_id, name: s.category } : null;
    modal.show = true;
    form.clearErrors();
    form.name             = s.name;
    form.category_id      = s.category_id;
    form.cost_price       = s.cost_price;
    form.selling_price    = s.selling_price;
    form.duration_minutes = s.duration_minutes;
    form.is_active        = s.is_active;
}

function save() {
    const options = { preserveScroll: true, onSuccess: () => { modal.show = false; } };
    if (modal.id) {
        form.put(route('catalog.services.update', modal.id), options);
    } else {
        form.post(route('catalog.services.store'), options);
    }
}
</script>
