<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/40" @click="$emit('cancel')"/>

            <!-- Dialog -->
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
                <!-- Icon + Title -->
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">{{ title }}</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ subtitle }}</p>
                    </div>
                </div>

                <!-- Warning -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4 text-sm text-amber-800 flex gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    Sẽ bị xóa vĩnh viễn sau <strong class="mx-1">10 phút</strong>. Bạn có thể hoàn tác trước đó.
                </div>

                <!-- Reason -->
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Lý do xóa <span class="text-red-500">*</span>
                </label>
                <textarea
                    v-model="reason"
                    ref="textareaRef"
                    rows="3"
                    placeholder="Nhập lý do xóa..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent resize-none"
                    :class="{ 'border-red-400 ring-1 ring-red-400': submitted && !reason.trim() }"
                />
                <p v-if="submitted && !reason.trim()" class="text-xs text-red-500 mt-1">Vui lòng nhập lý do xóa.</p>

                <!-- Actions -->
                <div class="flex justify-end gap-3 mt-5">
                    <button @click="$emit('cancel')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Hủy bỏ
                    </button>
                    <button @click="confirm"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                        Xác nhận xóa
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';

const props = defineProps({
    show:     { type: Boolean, default: false },
    title:    { type: String, default: 'Xác nhận xóa' },
    subtitle: { type: String, default: '' },
});

const emit = defineEmits(['confirm', 'cancel']);

const reason      = ref('');
const submitted   = ref(false);
const textareaRef = ref(null);

watch(() => props.show, (val) => {
    if (val) {
        reason.value    = '';
        submitted.value = false;
        nextTick(() => textareaRef.value?.focus());
    }
});

function confirm() {
    submitted.value = true;
    if (!reason.value.trim()) return;
    emit('confirm', reason.value.trim());
}
</script>
