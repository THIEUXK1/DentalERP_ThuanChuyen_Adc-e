<template>
    <AppLayout :title="`Bảng giá: ${priceList.name}`">
        <div class="max-w-4xl space-y-4">
            <div class="flex items-center gap-3">
                <Link :href="route('catalog.price-lists.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Quay lại</Link>
                <h2 class="text-lg font-semibold text-gray-800">{{ priceList.name }}</h2>
                <StatusBadge :color="priceList.is_active ? 'green' : 'gray'">
                    {{ priceList.is_active ? 'Hoạt động' : 'Vô hiệu' }}
                </StatusBadge>
            </div>

            <!-- Add service -->
            <div v-if="can('services.manage') && services.length > 0" class="bg-white rounded-xl border border-gray-200 p-4">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Thêm dịch vụ vào bảng giá</h3>
                <div class="flex gap-3 items-end">
                    <div class="flex-1">
                        <select v-model="addForm.service_id"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn dịch vụ --</option>
                            <option v-for="s in services" :key="s.id" :value="s.id">
                                {{ s.code }} — {{ s.name }} ({{ formatVnd(s.selling_price) }})
                            </option>
                        </select>
                    </div>
                    <div class="w-40">
                        <input v-model="addForm.unit_price" type="number" min="0" placeholder="Giá bảng (₫)"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </div>
                    <button @click="submitAdd" :disabled="addForm.processing"
                        class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        Thêm
                    </button>
                </div>
            </div>

            <!-- Items table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Mã</th>
                            <th class="px-4 py-3 text-left font-medium">Tên dịch vụ</th>
                            <th class="px-4 py-3 text-right font-medium">Giá bảng này</th>
                            <th v-if="can('services.manage')" class="px-4 py-3 text-right font-medium">Xóa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="items.length === 0">
                            <td colspan="4" class="text-center py-8 text-gray-400">Chưa có dịch vụ trong bảng giá này</td>
                        </tr>
                        <tr v-for="i in items" :key="i.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ i.service_code }}</td>
                            <td class="px-4 py-3 text-gray-900">{{ i.service_name }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">{{ formatVnd(i.unit_price) }}</td>
                            <td v-if="can('services.manage')" class="px-4 py-3 text-right">
                                <button @click="removeItem(i.id)" class="text-red-500 hover:text-red-700 text-xs">Xóa</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';
import { useCurrency } from '@/composables/useCurrency';

const { hasPermission: can } = usePermission();
const { formatVnd } = useCurrency();
const props = defineProps({ priceList: Object, items: Array, services: Array });

const addForm = useForm({ service_id: '', unit_price: '' });

function submitAdd() {
    addForm.post(route('catalog.price-lists.items.add', props.priceList.id), {
        onSuccess: () => addForm.reset(),
    });
}

function removeItem(id) {
    router.delete(route('catalog.price-lists.items.remove', id));
}
</script>
