<template>
    <span v-if="delta !== null && delta !== undefined"
        class="inline-flex items-center gap-0.5 text-[11px] font-semibold px-1.5 py-0.5 rounded-full bg-white/20">
        <svg v-if="delta > 0" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 3l6 8H4l6-8z"/></svg>
        <svg v-else-if="delta < 0" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 17l-6-8h12l-6 8z"/></svg>
        {{ delta > 0 ? '+' : '' }}{{ delta }}%
    </span>
</template>

<script setup>
defineProps({
    delta: { type: [Number, null], default: null },
});
</script>
