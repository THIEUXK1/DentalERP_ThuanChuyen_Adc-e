<template>
    <div v-if="links.length > 3" class="flex items-center justify-between mt-4 text-sm">
        <p class="text-gray-500">
            Hiển thị {{ meta.from ?? 0 }}–{{ meta.to ?? 0 }} / {{ meta.total }} kết quả
        </p>
        <div class="flex gap-1">
            <template v-for="link in links" :key="link.label">
                <button
                    v-if="link.url"
                    @click="$emit('navigate', link.url)"
                    :class="[
                        'px-3 py-1 rounded border text-xs',
                        link.active
                            ? 'bg-primary-600 text-white border-primary-600'
                            : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50',
                    ]"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="px-3 py-1 rounded border text-xs text-gray-300 border-gray-200"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>

<script setup>
defineProps({
    links: { type: Array, default: () => [] },
    meta:  { type: Object, default: () => ({}) },
});
defineEmits(['navigate']);
</script>
