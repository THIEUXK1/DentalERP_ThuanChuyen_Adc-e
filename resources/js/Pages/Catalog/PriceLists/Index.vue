<template>
    <AppLayout title="Bảng giá">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Bảng giá dịch vụ</h2>
                <button v-if="can('services.manage')" @click="showCreate = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Thêm bảng giá
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link v-for="p in priceLists" :key="p.id" :href="route('catalog.price-lists.show', p.id)"
                    class="bg-white rounded-xl border border-gray-200 p-5 hover:border-primary-300 hover:shadow-sm transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-semibold text-gray-800">{{ p.name }}</span>
                        <StatusBadge :color="p.is_active ? 'green' : 'gray'">
                            {{ p.is_active ? 'Hoạt động' : 'Vô hiệu' }}
                        </StatusBadge>
                    </div>
                    <p class="text-sm text-gray-500">{{ p.items_count }} dịch vụ · {{ p.code }}</p>
                </Link>
            </div>
        </div>

        <!-- Create modal -->
        <Modal :show="showCreate" title="Tạo bảng giá mới" @close="showCreate = false">
            <FormInput label="Tên bảng giá" :error="createForm.errors.name" required>
                <input v-model="createForm.name" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
            </FormInput>
            <template #footer>
                <button type="button" @click="showCreate = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                <button @click="submitCreate" :disabled="createForm.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">Tạo</button>
            </template>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Modal from '@/Components/Shared/Modal.vue';
import FormInput from '@/Components/Shared/FormInput.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
defineProps({ priceLists: Array });

const showCreate  = ref(false);
const createForm  = useForm({ name: '', is_active: true });

function submitCreate() {
    createForm.post(route('catalog.price-lists.store'), {
        onSuccess: () => { showCreate.value = false; createForm.reset(); },
    });
}
</script>
