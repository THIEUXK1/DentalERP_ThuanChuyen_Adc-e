<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-200" leave-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0" leave-to-class="opacity-0">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="$emit('close')" />
                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6 z-10">
                    <h3 v-if="title" class="text-lg font-semibold text-gray-900 mb-4">{{ title }}</h3>
                    <slot />
                    <div v-if="$slots.footer" class="mt-6 flex justify-end gap-3">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
defineProps({
    show:  { type: Boolean, default: false },
    title: { type: String, default: '' },
});
defineEmits(['close']);
</script>
