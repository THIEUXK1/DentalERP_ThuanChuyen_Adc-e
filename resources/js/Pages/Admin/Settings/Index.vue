<template>
    <AppLayout title="Cấu hình hệ thống">
        <div class="max-w-3xl space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">Cấu hình hệ thống</h2>

            <form @submit.prevent="submit">
                <div v-for="(keys, group) in settings" :key="group" class="bg-white rounded-xl border border-gray-200 p-5 mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 capitalize mb-4">{{ groupLabel(group) }}</h3>
                    <div class="space-y-4">
                        <FormInput v-for="(meta, key) in keys" :key="key" :label="meta.label">
                            <input
                                v-model="form[key]"
                                :type="meta.type"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"
                            />
                        </FormInput>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" :disabled="saving"
                        class="px-6 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        Lưu cấu hình
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import FormInput from '@/Components/Shared/FormInput.vue';

const props = defineProps({ settings: Object });

// Flatten settings into form
const form = reactive({});
for (const group of Object.values(props.settings)) {
    for (const [key, meta] of Object.entries(group)) {
        form[key] = meta.value ?? '';
    }
}

const saving = ref(false);

function submit() {
    saving.value = true;
    router.post(route('admin.settings.update'), { settings: form }, {
        onFinish: () => { saving.value = false; },
    });
}

const groupLabels = { clinic: 'Thông tin phòng khám', schedule: 'Lịch làm việc' };
function groupLabel(group) {
    return groupLabels[group] ?? group;
}
</script>
