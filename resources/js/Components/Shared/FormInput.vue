<template>
    <div class="flex flex-col gap-1">
        <label v-if="label" :for="id" class="text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>
        <slot>
            <input
                :id="id"
                v-bind="$attrs"
                :class="[
                    'block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500',
                    error ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white',
                ]"
            />
        </slot>
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<script setup>
defineProps({
    id:       { type: String, default: () => Math.random().toString(36).slice(2) },
    label:    { type: String, default: '' },
    error:    { type: String, default: '' },
    required: { type: Boolean, default: false },
});
</script>
